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
        $this->db = $db ?? Database::getInstance();
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
            $user = ApiAuth::requireAuth();
            $userId = (int)$user['id'];
        } else {
            Auth::check();
            $userId = (int)$_SESSION['user']['id'];
        }

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $filters = [
            'effectues'    => isset($_GET['effectues']),
            'departements' => $_GET['departement'] ?? [],
            'poiz_id'      => $_GET['poiz_id'] ?? null,
            'search'       => trim($_GET['search'] ?? '')
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

        $poiz  = $this->poiz->getAll();
        $title = 'Parcours';

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
        }

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
            WHERE p.titre LIKE :q
               OR p.ville LIKE :q
            ORDER BY p.titre ASC
            LIMIT 20
        ");

        $stmt->execute(['q' => "%$q%"]);

        Response::json([
            'success' => true,
            'data'    => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
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

        if (($user['role'] ?? '') === 'admin') {
            Response::json([
                'success' => false,
                'message' => 'Un administrateur ne peut pas valider'
            ], 403);
        }

        $parcoursId = (int)($_POST['parcours_id'] ?? 0);

        if ($this->maintenance->isInMaintenance($parcoursId)) {
            Response::json([
                'success' => false,
                'message' => 'Parcours en maintenance'
            ], 409);
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

        Toast::add('success', 'Parcours validé');
        header('Location: /parcours');
        exit;
    }

    /* =========================================================
       RESET PARCOURS
    ========================================================= */
    public function reset(): void
    {
        $user = ApiAuth::requireAuth();

        $payload = json_decode(file_get_contents('php://input'), true);

        $stmt = $this->db->prepare("
            DELETE FROM parcours_effectues
            WHERE user_id = ?
              AND parcours_id = ?
        ");

        $stmt->execute([
            (int)$user['id'],
            (int)($payload['parcours_id'] ?? 0)
        ]);

        Response::json([
            'success' => true,
            'message' => 'Parcours réinitialisé'
        ]);
    }

    /* =========================================================
       DEPARTEMENTS
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
            '79' => 'Deux-Sèvres',
            '86' => 'Vienne',
            '87' => 'Haute-Vienne'
        ];
    }
}
