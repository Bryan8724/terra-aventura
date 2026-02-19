<?php

namespace Controllers;

use Core\Database;
use Core\Auth;
use Core\Response;
use Core\ApiAuth;

class AuthController
{
    /* =========================
       FORM LOGIN (WEB)
    ========================= */
    public function loginForm(): void
    {
        $error = null;
        require VIEW_PATH . '/auth/login.php';
    }

    /* =========================
       LOGIN (WEB + API)
    ========================= */
    public function login(): void
    {
        // 🔐 Détection API SAFE (compatible PHP 8.3 + proxy)
        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        $path = is_string($uri) ? (parse_url($uri, PHP_URL_PATH) ?? '') : '';
        $isApi = str_starts_with($path, '/api/');

        $login    = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        /* =========================
           VALIDATION
        ========================= */
        if ($login === '' || $password === '') {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Champs obligatoires manquants'
                ], 400);
            }

            $error = 'Champs obligatoires manquants';
            require VIEW_PATH . '/auth/login.php';
            return;
        }

        /* =========================
           RECHERCHE UTILISATEUR
        ========================= */
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE (username = :username OR email = :email)
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([
            'username' => $login,
            'email'    => $login
        ]);

        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Identifiants invalides'
                ], 401);
            }

            $error = 'Identifiants invalides';
            require VIEW_PATH . '/auth/login.php';
            return;
        }

        /* =========================
           🔐 VERSION API → TOKEN
        ========================= */
        if ($isApi) {

            $token = ApiAuth::generateToken((int)$user['id']);

            Response::json([
                'success'    => true,
                'token'      => $token,
                'expires_in' => 604800, // 7 jours
                'user'       => [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'email'    => $user['email'],
                    'role'     => $user['role'],
                ]
            ]);
        }

        /* =========================
           🌐 VERSION WEB → SESSION
        ========================= */
        Auth::login($user);

        header('Location: /');
        exit;
    }

    /* =========================
       LOGOUT (WEB + API)
    ========================= */
    public function logout(): void
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        $path = is_string($uri) ? (parse_url($uri, PHP_URL_PATH) ?? '') : '';
        $isApi = str_starts_with($path, '/api/');

        if ($isApi) {

            ApiAuth::invalidateCurrentToken();

            Response::json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);
        }

        Auth::logout();

        header('Location: /login');
        exit;
    }

    /* =========================
       MOT DE PASSE OUBLIÉ (WEB)
    ========================= */
    public function forgotPassword(): void
    {
        $message = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = trim($_POST['email'] ?? '');

            if ($email === '') {
                $message = "Email requis.";
                require VIEW_PATH . '/auth/forgot_password.php';
                return;
            }

            $pdo = Database::getInstance();

            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE email = :email
                  AND status = 'active'
                LIMIT 1
            ");

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $pdo->prepare("
                    INSERT INTO password_requests (user_id, email)
                    VALUES (:user_id, :email)
                ")->execute([
                    'user_id' => $user['id'],
                    'email'   => $email,
                ]);
            }

            $message = "Votre demande a été envoyée à l’administrateur.";
        }

        require VIEW_PATH . '/auth/forgot_password.php';
    }
}
