<?php

namespace Controllers;

use Core\Auth;
use Core\ApiAuth;
use Core\Response;
use Core\Database;
use PDO;

class StatsController
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function index(): void
    {
        $uri   = $_SERVER['REQUEST_URI'] ?? '';
        $path  = parse_url($uri, PHP_URL_PATH) ?? '';
        $isApi = str_starts_with($path, '/api/');

        if ($isApi) {
            $user   = ApiAuth::requireAuth();
            $userId = (int)$user['id'];

            $myStats = $this->buildStats($userId);

            Response::json([
                'success' => true,
                'data'    => $myStats,
            ]);
            return;
        }

        Auth::check();
        $userId    = (int)$_SESSION['user']['id'];
        $compareId = (int)($_GET['compare'] ?? 0);

        $myStats      = $this->buildStats($userId);
        $compareStats = null;
        $compareUser  = null;

        if ($compareId > 0 && $compareId !== $userId) {
            $stmt = $this->db->prepare("SELECT id, username FROM users WHERE id = ? AND status = 'active'");
            $stmt->execute([$compareId]);
            $compareUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($compareUser) {
                $compareStats = $this->buildStats($compareId);
            }
        }

        $stmt = $this->db->prepare(
            "SELECT id, username FROM users WHERE status = 'active' AND id != ? ORDER BY username ASC"
        );
        $stmt->execute([$userId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $title = 'Statistiques';

        ob_start();
        require VIEW_PATH . '/stats/index.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    private function buildStats(int $userId): array
    {
        $s = [];

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            WHERE pe.user_id = ? AND p.poiz_id != 32
        ");
        $stmt->execute([$userId]);
        $s['parcours_effectues'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM parcours WHERE poiz_id != 32");
        $stmt->execute();
        $s['parcours_total'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT po.nom AS poiz_nom,
                   COUNT(pe.id) AS nb_effectues,
                   (SELECT COUNT(*) FROM parcours p2 WHERE p2.poiz_id = po.id AND p2.poiz_id != 32) AS nb_total
            FROM poiz po
            LEFT JOIN parcours p ON p.poiz_id = po.id AND p.poiz_id != 32
            LEFT JOIN parcours_effectues pe ON pe.parcours_id = p.id AND pe.user_id = ?
            WHERE po.id != 32
            GROUP BY po.id
            HAVING nb_total > 0
            ORDER BY nb_effectues DESC, po.nom ASC
        ");
        $stmt->execute([$userId]);
        $s['par_poiz'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            WHERE pe.user_id = ? AND p.poiz_id = 32
        ");
        $stmt->execute([$userId]);
        $s['zamela_effectues'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM parcours WHERE poiz_id = 32");
        $s['zamela_total'] = (int)$stmt->fetchColumn();

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM evenement_effectues WHERE user_id = ?");
            $stmt->execute([$userId]);
            $s['evenements_effectues'] = (int)$stmt->fetchColumn();
            $s['evenements_total'] = (int)$this->db->query("SELECT COUNT(*) FROM evenements")->fetchColumn();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM evenement_parcours_effectues WHERE user_id = ?");
            $stmt->execute([$userId]);
            $s['ep_effectues'] = (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            $s['evenements_effectues'] = 0;
            $s['evenements_total']     = 0;
            $s['ep_effectues']         = 0;
        }

        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(p.distance_km), 0)
            FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            WHERE pe.user_id = ?
        ");
        $stmt->execute([$userId]);
        $s['distance_km'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(badges_recuperes), 0) FROM parcours_effectues WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $s['badges'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT MIN(date_validation) AS premier, MAX(date_validation) AS dernier
            FROM parcours_effectues WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $dates = $stmt->fetch(PDO::FETCH_ASSOC);
        $s['premier_parcours'] = $dates['premier'];
        $s['dernier_parcours'] = $dates['dernier'];

        $stmt = $this->db->prepare("
            SELECT p.departement_code, p.departement_nom, COUNT(*) AS nb
            FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            WHERE pe.user_id = ? AND p.poiz_id != 32
            GROUP BY p.departement_code
            ORDER BY nb DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $s['dept_favori'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $this->db->prepare("
            SELECT ROUND(AVG(p.niveau), 1)
            FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            WHERE pe.user_id = ? AND p.poiz_id != 32
        ");
        $stmt->execute([$userId]);
        $s['niveau_moyen'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT p.titre, p.ville, p.departement_code, po.nom AS poiz_nom,
                   pe.date_validation
            FROM parcours_effectues pe
            JOIN parcours p ON p.id = pe.parcours_id
            JOIN poiz po ON po.id = p.poiz_id
            WHERE pe.user_id = ?
            ORDER BY pe.date_validation DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $s['recents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalItems = $s['parcours_total'] + $s['zamela_total'] + $s['evenements_total'];
        $doneItems  = $s['parcours_effectues'] + $s['zamela_effectues'] + $s['evenements_effectues'];
        $s['score_global'] = $totalItems > 0 ? round($doneItems / $totalItems * 100) : 0;

        return $s;
    }
}