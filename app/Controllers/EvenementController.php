<?php

namespace Controllers;

use Core\Auth;
use Core\ApiAuth;
use Core\AdminMiddleware;
use Core\Toast;
use Core\Database;
use Core\Response;
use Models\Evenement;
use PDO;

class EvenementController
{
    private PDO       $db;
    private Evenement $evenement;

    public function __construct(?PDO $db = null)
    {
        $this->db        = $db ?? Database::getInstance();
        $this->evenement = new Evenement($this->db);
    }

    /* =========================================================
       LISTE
    ========================================================= */
    public function index(): void
    {
        $isApi = $this->isApi();

        if ($isApi) {
            $user   = ApiAuth::requireAuth();
            $userId = (int)$user['id'];
        } else {
            Auth::check();
            $userId = (int)$_SESSION['user']['id'];
        }

        $filters = [
            'search'       => trim($_GET['search'] ?? ''),
            'departement'  => trim($_GET['departement'] ?? ''),
            'effectues'    => isset($_GET['effectues']),
            'expired_only' => isset($_GET['expires']),
        ];

        $evenements = $this->evenement->getAll($userId, $filters);

        if ($isApi) {
            Response::json(['success' => true, 'data' => $evenements]);
        }

        if (!empty($_GET['ajax'])) {
            require VIEW_PATH . '/evenement/_list.php';
            exit;
        }

        $departements = $this->departements();
        $title        = isset($_GET['expires']) ? 'Événements expirés' : 'Événements';

        ob_start();
        require VIEW_PATH . '/evenement/index.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       CRÉATION (ADMIN)
    ========================================================= */
    public function create(): void
    {
        AdminMiddleware::handle();

        $departements = $this->departements();
        $title        = 'Ajouter un événement';

        ob_start();
        require VIEW_PATH . '/evenement/create.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       ENREGISTREMENT (ADMIN)
    ========================================================= */
    public function store(): void
    {
        AdminMiddleware::handle();

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->uploadImage($_FILES['image']);
        }

        $deptCode = trim($_POST['departement_code'] ?? '');
        $depts    = $this->departements();

        $evenementId = $this->evenement->create([
            'nom'              => trim($_POST['nom'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'date_debut'       => trim($_POST['date_debut'] ?? ''),
            'date_fin'         => trim($_POST['date_fin'] ?? ''),
            'image'            => $imagePath,
        ]);

        // Parcours associés envoyés en JSON
        $parcoursJson = trim($_POST['parcours_json'] ?? '[]');
        $parcoursList = json_decode($parcoursJson, true) ?: [];
        foreach ($parcoursList as $p) {
            if (!empty($p['titre'])) {
                $this->evenement->addParcours($evenementId, $p);
            }
        }

        Toast::add('success', 'Événement créé avec succès');
        header('Location: /evenement');
        exit;
    }

    /* =========================================================
       ÉDITION (ADMIN)
    ========================================================= */
    public function edit(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_GET['id'] ?? 0);
        $evt = $this->evenement->find($id);

        if (!$evt) {
            Toast::add('error', 'Événement introuvable');
            header('Location: /evenement');
            exit;
        }

        $departements = $this->departements();
        $title        = 'Modifier l\'événement';

        ob_start();
        require VIEW_PATH . '/evenement/edit.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       MISE À JOUR (ADMIN)
    ========================================================= */
    public function update(): void
    {
        AdminMiddleware::handle();

        $id  = (int)($_POST['id'] ?? 0);
        $evt = $this->evenement->find($id);

        if (!$evt) {
            Toast::add('error', 'Événement introuvable');
            header('Location: /evenement');
            exit;
        }

        $imagePath = $evt['image'];
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->uploadImage($_FILES['image']);
        }

        $deptCode = trim($_POST['departement_code'] ?? '');
        $depts    = $this->departements();

        $this->evenement->update($id, [
            'nom'              => trim($_POST['nom'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'date_debut'       => trim($_POST['date_debut'] ?? ''),
            'date_fin'         => trim($_POST['date_fin'] ?? ''),
            'image'            => $imagePath,
        ]);

        // Gérer les parcours : supprimer les supprimés, ajouter/update les autres
        $parcoursJson = trim($_POST['parcours_json'] ?? '[]');
        $parcoursList = json_decode($parcoursJson, true) ?: [];

        // IDs existants à garder
        $keepIds = array_filter(array_column($parcoursList, 'id'));

        // Supprimer ceux qui ne sont plus dans la liste
        if (!empty($keepIds)) {
            $in = implode(',', array_map('intval', $keepIds));
            $this->db->exec("DELETE FROM evenement_parcours WHERE evenement_id = $id AND id NOT IN ($in)");
        } else {
            $this->db->exec("DELETE FROM evenement_parcours WHERE evenement_id = $id");
        }

        foreach ($parcoursList as $p) {
            if (empty($p['titre'])) continue;
            if (!empty($p['id'])) {
                $this->evenement->updateParcours((int)$p['id'], $p);
            } else {
                $this->evenement->addParcours($id, $p);
            }
        }

        Toast::add('success', 'Événement mis à jour');
        header('Location: /evenement');
        exit;
    }

    /* =========================================================
       SUPPRESSION (ADMIN)
    ========================================================= */
    public function delete(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Événement introuvable');
            header('Location: /evenement');
            exit;
        }

        $this->evenement->delete($id);

        Toast::add('success', 'Événement supprimé');
        header('Location: /evenement');
        exit;
    }

    /* =========================================================
       VALIDER PARTICIPATION
    ========================================================= */
    public function valider(): void
    {
        Auth::check();
        $user = $_SESSION['user'];

        if (($user['role'] ?? '') === 'admin') {
            Toast::add('error', 'Les administrateurs ne peuvent pas valider un événement');
            header('Location: /evenement');
            exit;
        }

        $evenementId = (int)($_POST['evenement_id'] ?? 0);
        $this->evenement->validerEvenement(
            (int)$user['id'],
            $evenementId,
            $_POST['date'] ?? null
        );

        Toast::add('success', 'Participation validée !');
        header('Location: /evenement');
        exit;
    }

    /* =========================================================
       RESET PARTICIPATION
    ========================================================= */
    public function reset(): void
    {
        Auth::check();
        $user        = $_SESSION['user'];
        $evenementId = (int)($_POST['evenement_id'] ?? 0);

        $this->evenement->resetEvenement((int)$user['id'], $evenementId);

        Toast::add('success', 'Participation réinitialisée');
        header('Location: /evenement');
        exit;
    }

    /* =========================================================
       DÉTAIL ÉVÉNEMENT (page parcours internes)
    ========================================================= */
    public function detail(): void
    {
        Auth::check();
        $userId = (int)$_SESSION['user']['id'];
        $user   = $_SESSION['user'];
        $id     = (int)($_GET['id'] ?? 0);

        $evt = $this->evenement->findForUser($id, $userId);

        if (!$evt) {
            Toast::add('error', 'Événement introuvable');
            header('Location: /evenement');
            exit;
        }

        $isAdmin = ($user['role'] ?? '') === 'admin';
        $title   = htmlspecialchars($evt['nom']);

        ob_start();
        require VIEW_PATH . '/evenement/detail.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       VALIDER PARCOURS D'ÉVÉNEMENT
    ========================================================= */
    public function validerParcours(): void
    {
        Auth::check();
        $user = $_SESSION['user'];

        if (($user['role'] ?? '') === 'admin') {
            Toast::add('error', 'Un administrateur ne peut pas valider un parcours');
            header('Location: /evenement');
            exit;
        }

        $epId        = (int)($_POST['ep_id'] ?? 0);
        $evenementId = (int)($_POST['evenement_id'] ?? 0);

        $this->evenement->validerParcours(
            (int)$user['id'],
            $epId,
            $_POST['date'] ?? null
        );

        Toast::add('success', 'Parcours validé !');
        header('Location: /evenement/detail?id=' . $evenementId);
        exit;
    }

    /* =========================================================
       RESET PARCOURS D'ÉVÉNEMENT
    ========================================================= */
    public function resetParcours(): void
    {
        Auth::check();
        $user        = $_SESSION['user'];
        $epId        = (int)($_POST['ep_id'] ?? 0);
        $evenementId = (int)($_POST['evenement_id'] ?? 0);

        $this->evenement->resetParcours((int)$user['id'], $epId);

        Toast::add('success', 'Parcours réinitialisé');
        header('Location: /evenement/detail?id=' . $evenementId);
        exit;
    }

    /* =========================================================
       UPLOAD IMAGE
    ========================================================= */
    private function uploadImage(array $file): string
    {
        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Type de fichier non autorisé : ' . $mime);
        }

        $dir = ROOT_PATH . '/public/uploads/evenements/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        if (!is_writable($dir)) throw new \RuntimeException('Dossier non accessible en écriture');

        $name = uniqid() . '_' . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            throw new \RuntimeException('Échec de l\'upload');
        }

        return '/uploads/evenements/' . $name;
    }

    /* =========================================================
       HELPER
    ========================================================= */
    private function isApi(): bool
    {
        return str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/api/');
    }

    private function departements(): array
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
}
