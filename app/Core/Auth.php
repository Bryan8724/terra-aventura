<?php

namespace Core;

class Auth
{
    /*
    |--------------------------------------------------------------------------
    | Session Start
    |--------------------------------------------------------------------------
    */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */
    public static function login(array $user): void
    {
        self::start();

        // 🔐 Protection contre fixation de session
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'] ?? null,
            'role'     => $user['role'],
            'status'   => $user['status'] ?? null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */
    public static function logout(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /*
    |--------------------------------------------------------------------------
    | Check Authentication
    |--------------------------------------------------------------------------
    */
    public static function check(): void
    {
        self::start();

        if (!isset($_SESSION['user'])) {

            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';

            if (str_starts_with($uri, '/api/')) {

                header('Content-Type: application/json');
                http_response_code(401);

                echo json_encode([
                    'success' => false,
                    'message' => 'Non authentifié'
                ]);
                exit;
            }

            header('Location: /login');
            exit;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Current User
    |--------------------------------------------------------------------------
    */
    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Is Admin
    |--------------------------------------------------------------------------
    */
    public static function isAdmin(): bool
    {
        self::start();
        return isset($_SESSION['user']) 
            && ($_SESSION['user']['role'] ?? null) === 'admin';
    }
}
