<?php

namespace Controllers;

use Core\Database;
use Core\AdminMiddleware;
use Core\Response;
use Core\ApiAuth;
use Core\Auth;

class AdminMessageController
{
    /* =========================
       LISTE DEMANDES
    ========================= */
    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        /*
        |--------------------------------------------------------------------------
        | Authentification
        |--------------------------------------------------------------------------
        */
        if ($isApi) {
            $user = ApiAuth::requireAuth();

            if (($user['role'] ?? '') !== 'admin') {
                Response::json([
                    'success' => false,
                    'message' => 'Accès réservé aux administrateurs'
                ], 403);
            }

        } else {
            AdminMiddleware::handle();
        }

        $pdo = Database::getInstance();

        $requests = $pdo->query("
            SELECT pr.*, u.username
            FROM password_requests pr
            JOIN users u ON u.id = pr.user_id
            ORDER BY pr.created_at DESC
        ")->fetchAll();

        /*
        |--------------------------------------------------------------------------
        | Version API
        |--------------------------------------------------------------------------
        */
        if ($isApi) {
            Response::json([
                'success' => true,
                'data'    => $requests
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Version Web
        |--------------------------------------------------------------------------
        */
        $title = 'Demandes de mot de passe';

        ob_start();
        require VIEW_PATH . '/admin/messages.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================
       TRAITEMENT DEMANDE
    ========================= */
    public function process(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        /*
        |--------------------------------------------------------------------------
        | Authentification
        |--------------------------------------------------------------------------
        */
        if ($isApi) {
            $user = ApiAuth::requireAuth();

            if (($user['role'] ?? '') !== 'admin') {
                Response::json([
                    'success' => false,
                    'message' => 'Accès réservé aux administrateurs'
                ], 403);
            }

        } else {
            AdminMiddleware::handle();
        }

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */
        $requestId = (int)($_POST['request_id'] ?? 0);
        $newPassword = $_POST['password'] ?? '';

        if ($requestId <= 0 || $newPassword === '') {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Paramètres manquants'
                ], 400);
            }

            header('Location: /admin/messages');
            exit;
        }

        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT user_id
            FROM password_requests
            WHERE id = :id
              AND status = 'pending'
            LIMIT 1
        ");

        $stmt->execute(['id' => $requestId]);
        $req = $stmt->fetch();

        if (!$req) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Demande introuvable ou déjà traitée'
                ], 404);
            }

            header('Location: /admin/messages');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction sécurisée
        |--------------------------------------------------------------------------
        */
        $pdo->beginTransaction();

        try {

            $pdo->prepare("
                UPDATE users
                SET password = :password
                WHERE id = :user_id
            ")->execute([
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'user_id'  => $req['user_id']
            ]);

            $pdo->prepare("
                UPDATE password_requests
                SET status = 'done',
                    processed_at = NOW()
                WHERE id = :id
            ")->execute([
                'id' => $requestId
            ]);

            $pdo->commit();

        } catch (\Throwable $e) {

            $pdo->rollBack();

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Erreur serveur'
                ], 500);
            }

            header('Location: /admin/messages');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Réponse API
        |--------------------------------------------------------------------------
        */
        if ($isApi) {
            Response::json([
                'success' => true,
                'message' => 'Mot de passe mis à jour'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Redirection Web
        |--------------------------------------------------------------------------
        */
        header('Location: /admin/messages');
        exit;
    }
}
