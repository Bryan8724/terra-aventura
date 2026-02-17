<?php

namespace Core;

class Csrf
{
    /*
    |--------------------------------------------------------------------------
    | Génération Token
    |--------------------------------------------------------------------------
    */
    public static function token(): string
    {
        self::start();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /*
    |--------------------------------------------------------------------------
    | Vérification Token
    |--------------------------------------------------------------------------
    */
    public static function check(): void
    {
        self::start();

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';

        // 👉 Pas de CSRF sur API
        if (str_starts_with($uri, '/api/')) {
            return;
        }

        $tokenPost   = $_POST['csrf_token'] ?? null;
        $tokenStored = $_SESSION['csrf_token'] ?? null;

        if (
            !$tokenPost ||
            !$tokenStored ||
            !hash_equals($tokenStored, $tokenPost)
        ) {

            // 👉 Version API
            if (str_starts_with($uri, '/api/')) {

                header('Content-Type: application/json');
                http_response_code(403);

                echo json_encode([
                    'success' => false,
                    'message' => 'CSRF invalide'
                ]);
                exit;
            }

            // 👉 Version Web
            http_response_code(403);
            exit('Action non autorisée (CSRF)');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Session start sécurisé
    |--------------------------------------------------------------------------
    */
    private static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
