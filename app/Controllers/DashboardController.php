<?php

namespace Controllers;

use Core\Auth;
use Core\Response;
use Core\ApiAuth;
use Core\Database;
use PDO;

class DashboardController
{
    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        if ($isApi) {
            $user = ApiAuth::requireAuth();
            Response::json([
                'success' => true,
                'message' => 'Dashboard chargé',
                'data'    => [
                    'user' => [
                        'id'       => $user['id'],
                        'username' => $user['username'],
                        'email'    => $user['email'],
                        'role'     => $user['role'],
                    ]
                ]
            ]);
        }

        Auth::check();

        $db     = Database::getInstance();
        $userId = (int)$_SESSION['user']['id'];

        // Compteurs dashboard
        $totalParcours = (int)$db->query("SELECT COUNT(*) FROM parcours")->fetchColumn();
        $totalPoiz     = (int)$db->query("SELECT COUNT(*) FROM poiz")->fetchColumn();

        $stmtEff = $db->prepare("SELECT COUNT(*) FROM parcours_effectues WHERE user_id = ?");
        $stmtEff->execute([$userId]);
        $effectues = (int)$stmtEff->fetchColumn();

        require VIEW_PATH . '/dashboard/index.php';
    }
}
