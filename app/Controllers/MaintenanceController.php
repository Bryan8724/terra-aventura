<?php

namespace Controllers;

use Core\Auth;
use Core\ApiAuth;
use Core\Toast;
use Core\Response;
use Models\Maintenance;

class MaintenanceController
{
    private Maintenance $maintenance;

    public function __construct()
    {
        $this->maintenance = new Maintenance();
    }

    /* =========================================================
       PAGE PRINCIPALE
    ========================================================= */
    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        /*
        |--------------------------------------------------------------------------
        | 🔐 VERSION API
        |--------------------------------------------------------------------------
        */
        if ($isApi) {

            $user = ApiAuth::requireAuth();

            $limit  = 25;
            $offset = 0;

            $parcours = $this->maintenance->getParcoursPaginated($limit, $offset);
            $meta     = $this->maintenance->getMeta();

            Response::json([
                'success' => true,
                'data'    => [
                    'parcours' => $parcours,
                    'meta'     => $meta
                ]
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🌐 VERSION WEB
        |--------------------------------------------------------------------------
        */
        Auth::check();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $limit  = 25;
        $offset = 0;

        $parcours = $this->maintenance->getParcoursPaginated($limit, $offset);
        $meta     = $this->maintenance->getMeta();

        $title = 'Maintenance';

        ob_start();
        require VIEW_PATH . '/maintenance/index.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       PAGINATION PARCOURS
    ========================================================= */
    public function ajaxParcours(): void
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

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $total      = $this->maintenance->countParcours();
        $totalPages = (int) ceil($total / $limit);
        $parcours   = $this->maintenance->getParcoursPaginated($limit, $offset);

        Response::json([
            'success'    => true,
            'data'       => $parcours,
            'page'       => $page,
            'totalPages' => $totalPages
        ]);
    }

    /* =========================================================
       HISTORIQUE AVEC DIFF
    ========================================================= */
    public function ajaxHistoryDiff(): void
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

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 5;
        $offset = ($page - 1) * $limit;

        $total      = $this->maintenance->countHistory();
        $totalPages = (int) ceil($total / $limit);

        $history        = $this->maintenance->getHistoryPaginated($limit, $offset);
        $parcoursTitles = $this->maintenance->getParcoursTitles();
        $currentIds     = array_column($this->maintenance->getAll(), 'id');

        $result = [];

        foreach ($history as $index => $h) {

            $snapshot = json_decode($h['snapshot'] ?? '[]', true) ?: [];

            if ($page === 1 && $index === 0) {
                $newState = $currentIds;
            } else {
                $prevIndex = $index - 1;

                if ($prevIndex >= 0 && isset($history[$prevIndex])) {
                    $newState = json_decode(
                        $history[$prevIndex]['snapshot'] ?? '[]',
                        true
                    ) ?: [];
                } else {
                    $newState = [];
                }
            }

            $oldState = $snapshot;

            $added   = array_values(array_diff($newState, $oldState));
            $removed = array_values(array_diff($oldState, $newState));

            $result[] = [
                'id'         => $h['id'],
                'updated_at' => $h['updated_at'],
                'username'   => $h['username'],
                'added'      => array_map(
                    fn($id) => $parcoursTitles[$id] ?? 'Parcours supprimé',
                    $added
                ),
                'removed'    => array_map(
                    fn($id) => $parcoursTitles[$id] ?? 'Parcours supprimé',
                    $removed
                )
            ];
        }

        Response::json([
            'success'    => true,
            'data'       => $result,
            'page'       => $page,
            'totalPages' => $totalPages
        ]);
    }

    /* =========================================================
       UPDATE LISTE
    ========================================================= */
    public function update(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();
        } else {
            Auth::check();
            $this->checkCsrf();
            $user = $_SESSION['user'];
        }

        $currentMeta    = $this->maintenance->getMeta();
        $currentVersion = $currentMeta['updated_at'] ?? null;
        $clientVersion  = $_POST['version'] ?? null;

        if ($currentVersion !== $clientVersion) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Version modifiée par un autre utilisateur'
                ], 409);
            }

            Toast::add('error', 'Liste modifiée par un autre utilisateur.');
            header('Location: /maintenance');
            exit;
        }

        $ids = array_unique(
            array_filter(
                array_map('intval', (array)($_POST['parcours'] ?? [])),
                fn($id) => $id > 0
            )
        );

        $this->maintenance->update(
            $ids,
            (int) $user['id']
        );

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Maintenance mise à jour'
            ]);
        }

        Toast::add('success', 'Liste maintenance mise à jour.');
        header('Location: /maintenance');
        exit;
    }

    /* =========================================================
       RESTORE VERSION
    ========================================================= */
    public function restore(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();
        } else {
            Auth::check();
            $this->checkCsrf();
            $user = $_SESSION['user'];
        }

        $historyId = (int)($_POST['history_id'] ?? 0);

        if ($historyId <= 0) {
            Response::json([
                'success' => false,
                'message' => 'ID invalide'
            ], 400);
        }

        $snapshot = $this->maintenance->getHistorySnapshot($historyId);

        if (!$snapshot) {
            Response::json([
                'success' => false,
                'message' => 'Version introuvable'
            ], 404);
        }

        $ids = json_decode($snapshot['snapshot'], true);

        if (!is_array($ids)) {
            Response::json([
                'success' => false,
                'message' => 'Snapshot invalide'
            ], 400);
        }

        $ids = array_unique(
            array_filter(
                array_map('intval', $ids),
                fn($id) => $id > 0
            )
        );

        $this->maintenance->update(
            $ids,
            (int) $user['id']
        );

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Version restaurée'
            ]);
        }

        Toast::add('success', 'Version restaurée avec succès.');
        header('Location: /maintenance');
        exit;
    }

    /* =========================================================
       CSRF (WEB ONLY)
    ========================================================= */
    private function checkCsrf(): void
    {
        if (
            empty($_POST['csrf_token']) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            exit('CSRF invalide');
        }
    }
}
