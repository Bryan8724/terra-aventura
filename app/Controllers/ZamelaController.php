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
use PDO;

class ZamelaController
{
    private PDO     $db;
    private Parcours $parcours;
    private Poiz    $poiz;

    public function __construct(?PDO $db = null)
    {
        $this->db      = $db ?? Database::getInstance();
        $this->parcours = new Parcours($this->db);
        $this->poiz    = new Poiz($this->db);
    }

    /* =========================================================
       LISTE ZAMÉLA
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

        $filters = [
            'zamela_only'    => true,
            'effectues'      => isset($_GET['effectues']),
            'departements'   => $_GET['departement'] ?? [],
            'search'         => trim($_GET['search'] ?? ''),
            'expired_only'   => isset($_GET['expires']),
            'include_expired'=> isset($_GET['expires']), // when showing expired, include them
        ];

        $zamelas    = $this->parcours->getAllWithFilters($userId, $filters, $limit, $offset);
        $total      = $this->parcours->countWithFilters($userId, $filters);
        $totalPages = (int)ceil($total / $limit);

        if ($isApi) {
            Response::json([
                'success'    => true,
                'data'       => $zamelas,
                'page'       => $page,
                'totalPages' => $totalPages,
            ]);
        }

        if (!empty($_GET['ajax'])) {
            require VIEW_PATH . '/zamela/_list.php';
            exit;
        }

        $departements = $this->departements();
        $title        = isset($_GET['expires']) ? 'Zaméla expirés' : 'Zaméla';

        ob_start();
        require VIEW_PATH . '/zamela/index.php';
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
        $title        = 'Ajouter un Zaméla';

        ob_start();
        require VIEW_PATH . '/zamela/create.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       ENREGISTREMENT (ADMIN)
    ========================================================= */
    public function store(): void
    {
        AdminMiddleware::handle();

        $this->parcours->create([
            'poiz_id'          => Parcours::ZAMELA_POIZ_ID,   // forcé = Zaméla
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

        Toast::add('success', 'Zaméla créé avec succès');
        header('Location: /zamela');
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
            Toast::add('error', 'Zaméla introuvable');
            header('Location: /zamela');
            exit;
        }

        $zamela = $this->parcours->find($id);

        if (!$zamela || (int)$zamela['poiz_id'] !== Parcours::ZAMELA_POIZ_ID) {
            Toast::add('error', 'Zaméla introuvable');
            header('Location: /zamela');
            exit;
        }

        $departements = $this->departements();
        $title        = 'Modifier le Zaméla';

        ob_start();
        require VIEW_PATH . '/zamela/edit.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       MISE À JOUR (ADMIN)
    ========================================================= */
    public function update(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_POST['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Zaméla introuvable');
            header('Location: /zamela');
            exit;
        }

        $this->parcours->update($id, [
            'poiz_id'          => Parcours::ZAMELA_POIZ_ID,   // forcé = Zaméla
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

        Toast::add('success', 'Zaméla mis à jour');
        header('Location: /zamela');
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
            Toast::add('error', 'Zaméla introuvable');
            header('Location: /zamela');
            exit;
        }

        if (!$this->parcours->canDelete($id)) {
            Toast::add('error', 'Impossible de supprimer : ce Zaméla a été effectué par des utilisateurs');
            header('Location: /zamela');
            exit;
        }

        $this->parcours->delete($id);

        Toast::add('success', 'Zaméla supprimé');
        header('Location: /zamela');
        exit;
    }

    /* =========================================================
       DÉPARTEMENT (HELPER)
    ========================================================= */
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
