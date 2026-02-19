<?php

namespace Core;

class AdminMiddleware
{
    public static function handle(): void
    {
        Auth::start();
        Auth::check();

        if (!Auth::isAdmin()) {

            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';

            // 👉 VERSION API
            if (str_starts_with($uri, '/api/')) {

                header('Content-Type: application/json');
                http_response_code(403);

                echo json_encode([
                    'success' => false,
                    'message' => 'Accès réservé aux administrateurs'
                ]);
                exit;
            }

            // 👉 VERSION WEB
            http_response_code(403);
            echo '403 - Accès réservé aux administrateurs';
            exit;
        }
    }
}
