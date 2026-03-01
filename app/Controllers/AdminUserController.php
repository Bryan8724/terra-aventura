<?php

namespace Controllers;

use Core\Database;
use Core\Response;
use Core\ApiAuth;
use Core\ErrorPage;
use Core\Toast;

class AdminUserController
{
    /* =========================
       UTILITAIRE AUTH ADMIN
    ========================= */
    private function requireAdmin(): void
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
                    'message' => 'Accès réservé aux administrateurs'
                ], 403);
            }

            return;
        }

        // ✅ FIX : page HTML propre au lieu de http_response_code(403) + exit (page blanche)
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            ErrorPage::render(403, 'Cette section est réservée aux administrateurs.');
        }
    }

    /* =========================
       LISTE
    ========================= */
    public function index(): void
    {
        $this->requireAdmin();

        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        $users = Database::getInstance()
            ->query("SELECT * FROM users ORDER BY created_at DESC")
            ->fetchAll();

        if ($isApi) {
            Response::json([
                'success' => true,
                'data'    => $users
            ]);
        }

        $title = 'Utilisateurs';

        ob_start();
        require VIEW_PATH . '/admin/users/index.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================
       CREATE
    ========================= */
    public function create(): void
    {
        $this->requireAdmin();

        $title = 'Créer un utilisateur';

        ob_start();
        require VIEW_PATH . '/admin/users/form.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================
       EDIT
    ========================= */
    public function edit(): void
    {
        $this->requireAdmin();

        $id = (int)($_GET['id'] ?? 0);

        if ($id === 0) {
            Toast::add('error', 'Utilisateur introuvable');
            header('Location: /admin/users');
            exit;
        }

        $stmt = Database::getInstance()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            Toast::add('error', 'Utilisateur introuvable');
            header('Location: /admin/users');
            exit;
        }

        $title = 'Modifier l\'utilisateur';

        ob_start();
        require VIEW_PATH . '/admin/users/form.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================
       STORE
    ========================= */
    public function store(): void
    {
        $this->requireAdmin();

        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'user';

        if ($username === '' || $email === '' || $password === '') {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Champs obligatoires manquants'
                ], 400);
            }

            header('Location: /admin/users');
            exit;
        }

        Database::getInstance()->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (:u, :e, :p, :r)
        ")->execute([
            'u' => $username,
            'e' => $email,
            'p' => password_hash($password, PASSWORD_DEFAULT),
            'r' => $role,
        ]);

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Utilisateur créé'
            ], 201);
        }

        header('Location: /admin/users');
        exit;
    }

    /* =========================
       UPDATE
    ========================= */
    public function update(): void
    {
        $this->requireAdmin();

        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        $id       = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'user';
        $status   = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $username === '' || $email === '') {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], 400);
            }

            header('Location: /admin/users');
            exit;
        }

        $pdo = Database::getInstance();

        $sql = "UPDATE users SET username=:u, email=:e, role=:r, status=:s";
        $data = [
            'u'  => $username,
            'e'  => $email,
            'r'  => $role,
            's'  => $status,
            'id' => $id,
        ];

        if ($password !== '') {
            $sql .= ", password=:p";
            $data['p'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id=:id";

        $pdo->prepare($sql)->execute($data);

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Utilisateur mis à jour'
            ]);
        }

        header('Location: /admin/users');
        exit;
    }

    /* =========================
       DELETE
    ========================= */
    public function delete(): void
    {
        $this->requireAdmin();

        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        $pdo    = Database::getInstance();
        $userId = (int)($_POST['id'] ?? 0);

        if ($userId <= 0) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'ID invalide'
                ], 400);
            }

            header('Location: /admin/users');
            exit;
        }

        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user && $user['role'] === 'admin') {

            $countAdmins = $pdo->query("
                SELECT COUNT(*) FROM users
                WHERE role = 'admin' AND status = 'active'
            ")->fetchColumn();

            if ($countAdmins <= 1) {

                if ($isApi) {
                    Response::json([
                        'success' => false,
                        'message' => 'Impossible de supprimer le dernier admin actif'
                    ], 403);
                }

                header('Location: /admin/users');
                exit;
            }
        }

        $pdo->prepare("DELETE FROM users WHERE id = :id")
            ->execute(['id' => $userId]);

        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Utilisateur supprimé'
            ]);
        }

        header('Location: /admin/users');
        exit;
    }
}