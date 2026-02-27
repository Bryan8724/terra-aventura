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
        $totalParcours = (int)$db->query("SELECT COUNT(*) FROM parcours WHERE poiz_id != 32")->fetchColumn();
        $totalPoiz     = (int)$db->query("SELECT COUNT(*) FROM poiz")->fetchColumn();
        $totalZamela   = (int)$db->query("SELECT COUNT(*) FROM parcours WHERE poiz_id = 32")->fetchColumn();

        // Événements (table peut ne pas encore exister en prod — on protège)
        try {
            $totalEvenements = (int)$db->query("SELECT COUNT(*) FROM evenements")->fetchColumn();
        } catch (\Exception $e) {
            $totalEvenements = 0;
        }

        $stmtEff = $db->prepare("
            SELECT COUNT(*) FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            WHERE pe.user_id = ? AND p.poiz_id != 32
        ");
        $stmtEff->execute([$userId]);
        $effectues = (int)$stmtEff->fetchColumn();

        $stmtZeff = $db->prepare("
            SELECT COUNT(*) FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            WHERE pe.user_id = ? AND p.poiz_id = 32
        ");
        $stmtZeff->execute([$userId]);
        $zamelaEffectues = (int)$stmtZeff->fetchColumn();

        try {
            $stmtEve = $db->prepare("SELECT COUNT(*) FROM evenement_effectues WHERE user_id = ?");
            $stmtEve->execute([$userId]);
            $evenementsEffectues = (int)$stmtEve->fetchColumn();
        } catch (\Exception $e) {
            $evenementsEffectues = 0;
        }

        require VIEW_PATH . '/dashboard/index.php';
    }
}
