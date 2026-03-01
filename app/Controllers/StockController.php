<?php

namespace Controllers;

use Models\Stock;
use Core\Auth;
use Core\Response;
use Core\Database;
use PDO;

class StockController
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function index(): void
    {
        Auth::check();

        $userId      = (int)($_SESSION['user']['id'] ?? 0);
        $stock       = new Stock($this->db);
        $items       = $stock->getStockUtilisateur($userId);
        $totalBadges = $stock->getTotalBadges($userId);
        $title       = '🏅 Stock de Badges';
        $section     = 'stock';

        ob_start();
        require VIEW_PATH . '/stock/index.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }

    /** AJAX : met à jour une quantité */
    public function update(): void
    {
        Auth::check();
        header('Content-Type: application/json');

        // CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            echo json_encode(['success' => false, 'error' => 'Token invalide']);
            return;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $poizId = (int)($_POST['poiz_id'] ?? 0);
        $action = $_POST['action'] ?? 'set';
        $valeur = (int)($_POST['valeur'] ?? 0);

        if (!$poizId) {
            echo json_encode(['success' => false, 'error' => 'POIZ invalide']);
            return;
        }

        $stock = new Stock($this->db);

        if ($action === 'set') {
            $stock->setQuantite($userId, $poizId, $valeur);
            $nouvelle = max(0, $valeur);
        } else {
            $delta    = $action === 'plus' ? 1 : -1;
            $nouvelle = $stock->ajusterQuantite($userId, $poizId, $delta);
        }

        echo json_encode(['success' => true, 'quantite' => $nouvelle]);
    }
}
