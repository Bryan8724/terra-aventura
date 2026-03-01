<?php

namespace Controllers;

use Core\Auth;
use Core\ApiAuth;
use Core\AdminMiddleware;
use Core\Toast;
use Core\Database;
use Core\Response;
use Models\Parcours;
use Models\Poiz;
use Models\Maintenance;
use PDO;

class ParcoursController
{
    private PDO $db;
    private Parcours $parcours;
    private Poiz $poiz;
    private Maintenance $maintenance;

    public function __construct(?PDO $db = null)
    {
        $this->db          = $db ?? Database::getInstance();
        $this->parcours    = new Parcours($this->db);
        $this->poiz        = new Poiz($this->db);
        $this->maintenance = new Maintenance();
    }

    /* =========================================================
       LISTE PARCOURS
    ========================================================= */
    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user   = ApiAuth::requireAuth();
            $userId = (int)$user['id'];
        } else {
            Auth::check();
            $userId = (int)$_SESSION['user']['id'];
        }

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $isArchivedTab = isset($_GET['archived']);

        $filters = [
            'effectues'     => isset($_GET['effectues']),
            'departements'  => $_GET['departement'] ?? [],
            'poiz_id'       => $_GET['poiz_id'] ?? null,
            'search'        => trim($_GET['search'] ?? ''),
            'archived_only' => $isArchivedTab,
        ];

        $parcours = $this->parcours->getAllWithFilters(
            $userId,
            $filters,
            $limit,
            $offset
        );

        $total      = $this->parcours->countWithFilters($userId, $filters);
        $totalPages = (int)ceil($total / $limit);

        if ($isApi) {
            Response::json([
                'success'    => true,
                'data'       => $parcours,
                'page'       => $page,
                'totalPages' => $totalPages
            ]);
        }

        if (!empty($_GET['ajax'])) {
            require VIEW_PATH . '/parcours/_list.php';
            exit;
        }

        $poiz         = $this->poiz->getAll();
        $departements = $this->departementsNouvelleAquitaine(); // ✅ FIX : manquait dans la vue
        $title        = $isArchivedTab ? 'Parcours archivés' : 'Parcours';

        ob_start();
        require VIEW_PATH . '/parcours/index.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       RECHERCHE
    ========================================================= */
    public function search(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            ApiAuth::requireAuth();
        } else {
            Auth::check();
        }

        $q = trim($_GET['q'] ?? '');

        if (strlen($q) < 2) {
            Response::json([
                'success' => true,
                'data'    => []
            ]);
            exit;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT
                    p.id,
                    p.titre,
                    p.ville,
                    p.departement_code,
                    p.departement_nom,
                    p.niveau,
                    p.terrain,
                    p.duree,
                    p.distance_km,
                    z.nom  AS poiz_nom,
                    z.logo AS poiz_logo
                FROM parcours p
                LEFT JOIN poiz z ON z.id = p.poiz_id
                WHERE (p.titre LIKE :q1 OR p.ville LIKE :q2)
                ORDER BY p.titre ASC
                LIMIT 20
            ");

            $stmt->execute(['q1' => "%$q%", 'q2' => "%$q%"]);

            Response::json([
                'success' => true,
                'data'    => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);

        } catch (\Throwable $e) {
            http_response_code(500);
            Response::json([
                'success' => false,
                'message' => 'Erreur SQL : ' . $e->getMessage()
            ]);
        }
    }

    /* =========================================================
       VALIDATION PARCOURS (USER)
    ========================================================= */
    public function valider(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();
        } else {
            Auth::check();
            $user = $_SESSION['user'];
        }

        // ✅ FIX : vérification admin correctement séparée web / API
        if (($user['role'] ?? '') === 'admin') {
            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Un administrateur ne peut pas valider un parcours'
                ], 403);
            }

            Toast::add('error', 'Les administrateurs ne peuvent pas valider un parcours');
            header('Location: /parcours');
            exit;
        }

        $parcoursId = (int)($_POST['parcours_id'] ?? 0);

        // ✅ FIX : vérification maintenance correctement séparée web / API
        if ($this->maintenance->isInMaintenance($parcoursId)) {
            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Ce parcours est temporairement indisponible (maintenance)'
                ], 409);
            }

            Toast::add('error', 'Ce parcours est temporairement indisponible (maintenance)');
            header('Location: /parcours');
            exit;
        }

        $this->parcours->validateForUser(
            (int)$user['id'],
            $parcoursId,
            $_POST['date'] ?? null,
            $_POST['heure'] ?? null,
            (int)($_POST['badges_recuperes'] ?? 0)
        );

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Parcours validé'
            ]);
        }

        Toast::add('success', 'Parcours validé !');
        header('Location: /parcours');
        exit;
    }

    /* =========================================================
       RESET PARCOURS
    ========================================================= */
    public function reset(): void
    {
        // ✅ FIX : supportait uniquement l'API, bloquait les sessions web
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();

            $payload    = json_decode(file_get_contents('php://input'), true);
            $parcoursId = (int)($payload['parcours_id'] ?? 0);
        } else {
            Auth::check();
            $user       = $_SESSION['user'];
            $parcoursId = (int)($_POST['parcours_id'] ?? 0);
        }

        if ($parcoursId === 0) {
            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'parcours_id manquant'
                ], 400);
            }

            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        $stmt = $this->db->prepare("
            DELETE FROM parcours_effectues
            WHERE user_id = ?
              AND parcours_id = ?
        ");

        $stmt->execute([
            (int)$user['id'],
            $parcoursId
        ]);

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Parcours réinitialisé'
            ]);
        }

        Toast::add('success', 'Parcours réinitialisé');
        header('Location: /parcours');
        exit;
    }

    /* =========================================================
       DÉPARTEMENT (HELPER)
    ========================================================= */
    private function departementsNouvelleAquitaine(): array
    {
        return [
            '16' => 'Charente',
            '17' => 'Charente-Maritime',
            '19' => 'Corrèze',
            '23' => 'Creuse',
            '24' => 'Dordogne',
            '33' => 'Gironde',
            '40' => 'Landes',
            '47' => 'Lot-et-Garonne',
            '64' => 'Pyrénées-Atlantiques',
            '75' => 'Paris',
            '79' => 'Deux-Sèvres',
            '86' => 'Vienne',
            '87' => 'Haute-Vienne',
        ];
    }

    /* =========================================================
       DÉTAIL PARCOURS EFFECTUÉ
    ========================================================= */
    public function effectue(): void
    {
        Auth::check();
        $userId     = (int)$_SESSION['user']['id'];
        $parcoursId = (int)($_GET['id'] ?? 0);

        if ($parcoursId === 0) {
            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        $data = $this->parcours->getEffectueDetail($userId, $parcoursId);

        if (!$data) {
            Toast::add('error', 'Vous n\'avez pas encore effectué ce parcours');
            header('Location: /parcours');
            exit;
        }

        // ✅ FIX : tables réelles = quete_objet_parcours + quete_objets (via quete_objet_id)
        $stmt = $this->db->prepare("
            SELECT qo.nom,
                   (
                       SELECT COUNT(*)
                       FROM quete_objet_parcours qop2
                       INNER JOIN parcours_effectues pe2
                           ON pe2.parcours_id = qop2.parcours_id
                          AND pe2.user_id = :uid2
                       WHERE qop2.quete_objet_id = qop.quete_objet_id
                   ) AS total_obtenus
            FROM quete_objet_parcours qop
            INNER JOIN quete_objets qo ON qo.id = qop.quete_objet_id
            WHERE qop.parcours_id = :pid
            LIMIT 1
        ");

        $stmt->execute([':uid2' => $userId, ':pid' => $parcoursId]);
        $objet = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        $dernierParcours = $objet && (int)$objet['total_obtenus'] === 1;

        $title = 'Parcours effectué';
        ob_start();
        require VIEW_PATH . '/parcours/effectue.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       CRÉATION PARCOURS (ADMIN)
    ========================================================= */
    public function create(): void
    {
        AdminMiddleware::handle();

        $fromArchived = isset($_GET['from_archived']);
        $departements = $this->departementsNouvelleAquitaine();
        $poiz         = $this->poiz->getAll();
        $title        = $fromArchived ? 'Ajouter un parcours archivé' : 'Ajouter un parcours';

        ob_start();
        require VIEW_PATH . '/parcours/create.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       ENREGISTREMENT PARCOURS (ADMIN)
    ========================================================= */
    public function store(): void
    {
        AdminMiddleware::handle();

        $newId = $this->parcours->create([
            'poiz_id'          => (int)($_POST['poiz_id'] ?? 0),
            'titre'            => trim($_POST['titre'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => trim($_POST['departement_code'] ?? ''),
            'departement_nom'  => trim($_POST['departement_nom'] ?? ''),
            'niveau'           => (int)($_POST['niveau'] ?? 3),
            'terrain'          => (int)($_POST['terrain'] ?? 3),
            'duree'            => trim($_POST['duree'] ?? ''),
            'distance_km'      => (float)($_POST['distance_km'] ?? 0),
            'date_debut'       => trim($_POST['date_debut'] ?? ''),
            'date_fin'         => trim($_POST['date_fin'] ?? ''),
        ]);

        // Si création depuis l'onglet archivé → archiver immédiatement
        if (!empty($_POST['from_archived'])) {
            $this->parcours->archive($newId);
            Toast::add('success', 'Parcours créé et archivé avec succès');
            header('Location: /parcours?archived=1');
            exit;
        }

        Toast::add('success', 'Parcours créé avec succès');
        header('Location: /parcours');
        exit;
    }

    /* =========================================================
       FORMULAIRE ÉDITION (ADMIN)
    ========================================================= */
    public function edit(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_GET['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        $parcours = $this->parcours->find($id);

        if (!$parcours) {
            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        $departements = $this->departementsNouvelleAquitaine();
        $poiz         = $this->poiz->getAll();
        $title        = 'Modifier le parcours';

        ob_start();
        require VIEW_PATH . '/parcours/edit.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       MISE À JOUR PARCOURS (ADMIN)
    ========================================================= */
    public function update(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        $this->parcours->update($id, [
            'poiz_id'          => (int)($_POST['poiz_id'] ?? 0),
            'titre'            => trim($_POST['titre'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => trim($_POST['departement_code'] ?? ''),
            'departement_nom'  => trim($_POST['departement_nom'] ?? ''),
            'niveau'           => (int)($_POST['niveau'] ?? 3),
            'terrain'          => (int)($_POST['terrain'] ?? 3),
            'duree'            => trim($_POST['duree'] ?? ''),
            'distance_km'      => (float)($_POST['distance_km'] ?? 0),
            'date_debut'       => trim($_POST['date_debut'] ?? ''),
            'date_fin'         => trim($_POST['date_fin'] ?? ''),
        ]);

        Toast::add('success', 'Parcours mis à jour');
        header('Location: /parcours');
        exit;
    }

    /* =========================================================
       SUPPRESSION PARCOURS (ADMIN)
    ========================================================= */
    public function delete(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        if (!$this->parcours->canDelete($id)) {
            Toast::add('error', 'Impossible de supprimer : ce parcours a été effectué par des utilisateurs');
            header('Location: /parcours');
            exit;
        }

        $this->parcours->delete($id);

        Toast::add('success', 'Parcours supprimé');
        header('Location: /parcours');
        exit;
    }

    /* =========================================================
       ARCHIVER PARCOURS (ADMIN)
    ========================================================= */
    public function archiver(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        $this->parcours->archive($id);

        Toast::add('success', 'Parcours archivé');
        header('Location: /parcours');
        exit;
    }

    /* =========================================================
       DÉSARCHIVER PARCOURS (ADMIN)
    ========================================================= */
    public function desarchiver(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Parcours introuvable');
            header('Location: /parcours');
            exit;
        }

        $this->parcours->unarchive($id);

        Toast::add('success', 'Parcours désarchivé');
        header('Location: /parcours');
        exit;
    }
}