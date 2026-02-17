<?php

namespace Controllers;

use PDO;
use Exception;
use Core\Toast;
use Core\Response;
use Core\ApiAuth;
use Models\Quete;

class AdminQueteController
{
    private Quete $quete;

    public function __construct(private PDO $db)
    {
        $this->quete = new Quete($db);
    }

    /* =========================
       UTILITAIRE AUTH
    ========================= */
    private function requireAdmin(): array|null
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

            return $user;
        }

        // Web classique
        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit;
        }

        return null;
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

        $rows = $this->quete->getAllCompletes(null);

        $quetes = [];

        foreach ($rows as $row) {

            $qid = (int)($row['quete_id'] ?? 0);
            if ($qid <= 0) continue;

            $quetes[$qid] ??= [
                'id'     => $qid,
                'nom'    => (string)($row['quete_nom'] ?? ''),
                'saison' => $row['saison'] ?? null,
                'objets' => [],
            ];

            if (!empty($row['objet_id'])) {

                $oid = (int)$row['objet_id'];

                $quetes[$qid]['objets'][$oid] ??= [
                    'id'       => $oid,
                    'nom'      => (string)($row['objet_nom'] ?? ''),
                    'parcours' => [],
                ];

                if (!empty($row['parcours_id'])) {

                    $pid = (int)$row['parcours_id'];

                    $quetes[$qid]['objets'][$oid]['parcours'][$pid] = [
                        'id'     => $pid,
                        'nom'    => (string)($row['parcours_nom'] ?? ''),
                        'ville'  => (string)($row['ville'] ?? ''),
                        'dep'    => (string)($row['departement'] ?? ''),
                        'logo'   => $row['poiz_logo'] ?? null,
                    ];
                }
            }
        }

        foreach ($quetes as &$q) {
            foreach ($q['objets'] as &$o) {
                $o['parcours'] = array_values($o['parcours']);
            }
            $q['objets'] = array_values($q['objets']);
        }

        $quetes = array_values($quetes);

        if ($isApi) {
            Response::json([
                'success' => true,
                'data'    => $quetes
            ]);
        }

        $title = 'Administration des quêtes';

        ob_start();
        require ROOT_PATH . '/Views/quetes/index.php';
        $content = ob_get_clean();

        require ROOT_PATH . '/Views/partials/layout.php';
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

        $nom    = trim($_POST['nom'] ?? '');
        $saison = $_POST['saison'] ?? null;
        $objets = $_POST['objets'] ?? [];

        if ($nom === '' || empty($objets)) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Nom et objets obligatoires'
                ], 400);
            }

            Toast::add('error', 'Nom et objets obligatoires');
            header('Location: /admin/quetes/create');
            exit;
        }

        try {

            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO quetes (nom, saison) VALUES (?, ?)"
            );

            $stmt->execute([$nom, $saison ?: null]);

            $queteId = (int)$this->db->lastInsertId();

            $this->quete->updateObjets($queteId, $objets);

            $this->db->commit();

            if ($isApi) {
                Response::json([
                    'success' => true,
                    'id'      => $queteId
                ], 201);
            }

            Toast::add('success', 'Quête créée');
            header('Location: /admin/quetes');
            exit;

        } catch (Exception) {

            $this->db->rollBack();

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Erreur serveur'
                ], 500);
            }

            Toast::add('error', 'Erreur création');
            header('Location: /admin/quetes');
            exit;
        }
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

        $id     = (int)($_POST['id'] ?? 0);
        $nom    = trim($_POST['nom'] ?? '');
        $saison = $_POST['saison'] ?? null;
        $objets = $_POST['objets'] ?? [];

        if ($id <= 0 || $nom === '') {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], 400);
            }

            Toast::add('error', 'Données invalides');
            header('Location: /admin/quetes');
            exit;
        }

        try {

            $this->db->beginTransaction();

            $this->quete->update($id, $nom, $saison ?: null);
            $this->quete->updateObjets($id, $objets);

            $this->db->commit();

            if ($isApi) {
                Response::json([
                    'success' => true
                ]);
            }

            Toast::add('success', 'Quête mise à jour');
            header('Location: /admin/quetes');
            exit;

        } catch (Exception) {

            $this->db->rollBack();

            if ($isApi) {
                Response::json([
                    'success' => false
                ], 500);
            }

            Toast::add('error', 'Erreur mise à jour');
            header('Location: /admin/quetes');
            exit;
        }
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

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'ID invalide'
                ], 400);
            }

            Toast::add('error', 'ID invalide');
            header('Location: /admin/quetes');
            exit;
        }

        $this->quete->delete($id);

        if ($isApi) {
            Response::json([
                'success' => true
            ]);
        }

        Toast::add('success', 'Quête supprimée');
        header('Location: /admin/quetes');
        exit;
    }
}
