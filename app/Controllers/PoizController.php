<?php

namespace Controllers;

use Models\Poiz;
use Core\Response;
use Core\Auth;
use Core\ApiAuth;
use Core\AdminMiddleware;
use Core\Database;
use PDO;

class PoizController
{
    private Poiz $poiz;
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db   = $db ?? Database::getInstance();
        $this->poiz = new Poiz($this->db);
    }

    /* =========================================================
       LISTE POIZ
    ========================================================= */
    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            ApiAuth::requireAuth();

            Response::json([
                'success' => true,
                'data'    => $this->poiz->getAll(false)
            ]);
        }

        Auth::check();

        $poiz  = $this->poiz->getAll(false);
        $title = 'POIZ';

        ob_start();
        require VIEW_PATH . '/poiz/index.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       CREATE (ADMIN)
    ========================================================= */
    public function create(): void
    {
        AdminMiddleware::handle();

        $title = 'Ajouter un POIZ';

        ob_start();
        require VIEW_PATH . '/poiz/form.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       EDIT (ADMIN)
    ========================================================= */
    public function edit(): void
    {
        AdminMiddleware::handle();

        $id = (int)($_GET['id'] ?? 0);
        $poiz = $this->poiz->getById($id);

        if (!$poiz) {
            http_response_code(404);
            exit('POIZ introuvable');
        }

        $title = 'Modifier un POIZ';

        ob_start();
        require VIEW_PATH . '/poiz/form.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       STORE (ADMIN)
    ========================================================= */
    public function store(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();
            if (($user['role'] ?? '') !== 'admin') {
                Response::json([
                    'success' => false,
                    'message' => 'Accès interdit'
                ], 403);
            }
        } else {
            AdminMiddleware::handle();
        }

        $logoPath = null;

        if (!empty($_FILES['logo']['name'])) {
            $logoPath = $this->uploadLogo($_FILES['logo']);
        }

        $this->poiz->create([
            'nom'   => $_POST['nom'] ?? '',
            'theme' => $_POST['theme'] ?? null,
            'logo'  => $logoPath
        ]);

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'POIZ créé'
            ], 201);
        }

        header('Location: /poiz');
        exit;
    }

    /* =========================================================
       UPDATE (ADMIN)
    ========================================================= */
    public function update(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();
            if (($user['role'] ?? '') !== 'admin') {
                Response::json([
                    'success' => false,
                    'message' => 'Accès interdit'
                ], 403);
            }
        } else {
            AdminMiddleware::handle();
        }

        $id   = (int)($_POST['id'] ?? 0);
        $poiz = $this->poiz->getById($id);

        if (!$poiz) {
            Response::json([
                'success' => false,
                'message' => 'POIZ introuvable'
            ], 404);
        }

        $logoPath = $poiz['logo'];

        if (!empty($_FILES['logo']['name'])) {
            $logoPath = $this->uploadLogo($_FILES['logo']);
        }

        $this->poiz->update($id, [
            'nom'   => $_POST['nom'] ?? '',
            'theme' => $_POST['theme'] ?? null,
            'logo'  => $logoPath
        ]);

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'POIZ mis à jour'
            ]);
        }

        header('Location: /poiz');
        exit;
    }

    /* =========================================================
       DELETE (ADMIN)
    ========================================================= */
    public function delete(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();
            if (($user['role'] ?? '') !== 'admin') {
                Response::json([
                    'success' => false,
                    'message' => 'Accès interdit'
                ], 403);
            }
        } else {
            AdminMiddleware::handle();
        }

        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            Response::json([
                'success' => false,
                'message' => 'ID invalide'
            ], 400);
        }

        $stmt = $this->db->prepare("
            SELECT logo
            FROM poiz
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $poiz = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$poiz) {
            Response::json([
                'success' => false,
                'message' => 'POIZ introuvable'
            ], 404);
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM parcours WHERE poiz_id = ?
        ");
        $stmt->execute([$id]);

        if ($stmt->fetchColumn() > 0) {
            Response::json([
                'success' => false,
                'message' => 'POIZ utilisé dans des parcours'
            ], 409);
        }

        if (!empty($poiz['logo'])) {
            $file = ROOT_PATH . '/public/' . ltrim($poiz['logo'], '/');
            if (is_file($file)) {
                unlink($file);
            }
        }

        $stmt = $this->db->prepare("DELETE FROM poiz WHERE id = ?");
        $stmt->execute([$id]);

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'POIZ supprimé'
            ]);
        }

        header('Location: /poiz');
        exit;
    }

    /* =========================================================
       UPLOAD LOGO
    ========================================================= */
    private function uploadLogo(array $file): string
    {
        $dir = ROOT_PATH . '/public/uploads/poiz/';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $name = uniqid() . '_' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $dir . $name);

        return '/uploads/poiz/' . $name;
    }
}
