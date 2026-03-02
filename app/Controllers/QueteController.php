<?php

namespace Controllers;

use Models\Quete;
use Core\Auth;
use Core\ApiAuth;
use Core\Response;
use Core\Database;
use PDO;

class QueteController
{
    private Quete $quete;
    private PDO   $db;

    public function __construct(private PDO $dbIn = null)
    {
        $this->db    = $dbIn ?? Database::getInstance();
        $this->quete = new Quete($this->db);
    }

    /* =========================================================
       GET /api/quetes  — Liste quêtes + état user
    ========================================================= */
    public function index(): void
    {
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/api/');

        if ($isApi) {
            $user = ApiAuth::requireAuth();
        } else {
            Auth::check();
            $user = $_SESSION['user'];
        }

        $isAdmin = (($user['role'] ?? '') === 'admin');
        $userId  = (int)$user['id'];

        // Données brutes
        $rowsAll = $this->quete->getAllCompletes(null);

        // Parcours validés + objets confirmés par l'user
        $confirmedObjets = [];
        $doneParcours    = [];

        if (!$isAdmin) {
            // Parcours effectués
            $stmt = $this->db->prepare("
                SELECT pe.parcours_id FROM parcours_effectues pe WHERE pe.user_id = ?
            ");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
                $doneParcours[(int)$pid] = true;
            }

            // Objets explicitement confirmés
            $stmt = $this->db->prepare("
                SELECT quete_objet_id FROM quete_objet_confirmes WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $oid) {
                $confirmedObjets[(int)$oid] = true;
            }
        }

        // Assemblage
        $quetes = [];
        foreach ($rowsAll as $row) {
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
                    'obtenu'   => !$isAdmin && isset($confirmedObjets[$oid]),
                    'parcours' => [],
                ];

                if (!empty($row['parcours_id'])) {
                    $pid = (int)$row['parcours_id'];
                    $parcoursEffectue = (!$isAdmin && isset($doneParcours[$pid]));

                    $quetes[$qid]['objets'][$oid]['parcours'][$pid] = [
                        'id'      => $pid,
                        'nom'     => (string)($row['parcours_nom'] ?? ''),
                        'ville'   => (string)($row['ville'] ?? ''),
                        'dep'     => (string)($row['departement'] ?? ''),
                        'logo'    => $row['poiz_logo'] ?? null,
                        'effectue'=> $parcoursEffectue,
                    ];
                }
            }
        }

        // Normalisation + calcul état global quête
        foreach ($quetes as &$q) {
            foreach ($q['objets'] as &$o) {
                $o['parcours'] = array_values($o['parcours']);

                // Indique si au moins un parcours est effectué mais objet pas encore confirmé
                $anyEffectue = !empty(array_filter($o['parcours'], fn($p) => $p['effectue']));
                $o['peut_confirmer'] = !$isAdmin && $anyEffectue && !$o['obtenu'];
            }
            $q['objets'] = array_values($q['objets']);

            $totalObjets = count($q['objets']);
            $obtenuCount = count(array_filter($q['objets'], fn($o) => $o['obtenu']));
            $q['objets_obtenus']  = $obtenuCount;
            $q['objets_total']    = $totalObjets;
            $q['tous_objets_ok']  = ($totalObjets > 0 && $obtenuCount === $totalObjets);
        }
        unset($q, $o);

        $quetes = array_values($quetes);

        // Parcours finaux par quête
        if (!$isAdmin) {
            $parcoursFinaux = $this->getParcoursFinaux($userId);
            foreach ($quetes as &$q) {
                $q['parcours_final'] = $parcoursFinaux[$q['id']] ?? [];
            }
            unset($q);
        }

        if ($isApi) {
            Response::json([
                'success' => true,
                'data'    => [
                    'user'   => [
                        'id'       => $user['id'],
                        'username' => $user['username'],
                        'role'     => $user['role'],
                    ],
                    'quetes' => $quetes,
                ],
            ]);
        }

        // Vue web
        $title = 'Quêtes';
        ob_start();
        require VIEW_PATH . '/quetes/index.php';
        $content = ob_get_clean();
        require VIEW_PATH . '/partials/layout.php';
    }

    /* =========================================================
       POST /api/quetes/confirmer-objet
       Body : quete_objet_id
    ========================================================= */
    public function confirmerObjet(): void
    {
        $user = $this->requireUser();
        $userId = (int)$user['id'];
        $objetId = (int)($_POST['quete_objet_id'] ?? 0);

        if (!$objetId) {
            Response::json(['success' => false, 'message' => 'Objet invalide'], 400);
        }

        // Vérifier qu'un parcours lié a bien été effectué
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM quete_objet_parcours qop
            JOIN parcours_effectues pe ON pe.parcours_id = qop.parcours_id AND pe.user_id = :uid
            WHERE qop.quete_objet_id = :oid
        ");
        $stmt->execute([':uid' => $userId, ':oid' => $objetId]);
        if ($stmt->fetchColumn() == 0) {
            Response::json(['success' => false, 'message' => 'Aucun parcours effectué pour cet objet'], 403);
        }

        // Confirmer
        $this->db->prepare("
            INSERT IGNORE INTO quete_objet_confirmes (user_id, quete_objet_id)
            VALUES (?, ?)
        ")->execute([$userId, $objetId]);

        // Vérifier si tous les objets de la quête sont maintenant obtenus
        $stmt = $this->db->prepare("
            SELECT q.id, q.nom,
                   COUNT(qo.id) AS total_objets,
                   SUM(CASE WHEN qoc.id IS NOT NULL THEN 1 ELSE 0 END) AS objets_confirmes
            FROM quete_objets qo
            JOIN quetes q ON q.id = qo.quete_id
            LEFT JOIN quete_objet_confirmes qoc ON qoc.quete_objet_id = qo.id AND qoc.user_id = :uid
            WHERE qo.quete_id = (SELECT quete_id FROM quete_objets WHERE id = :oid)
            GROUP BY q.id
        ");
        $stmt->execute([':uid' => $userId, ':oid' => $objetId]);
        $queteStatus = $stmt->fetch(PDO::FETCH_ASSOC);

        $tousObtenu = $queteStatus && (int)$queteStatus['total_objets'] === (int)$queteStatus['objets_confirmes'];

        Response::json([
            'success'      => true,
            'message'      => 'Objet confirmé !',
            'quete_debloquee' => $tousObtenu,
            'quete_nom'    => $queteStatus['nom'] ?? '',
            'quete_id'     => $queteStatus['id'] ?? 0,
        ]);
    }

    /* =========================================================
       POST /api/quetes/parcours-final/ajouter
       Body : quete_id, titre, ville, distance_km
    ========================================================= */
    public function ajouterParcoursFinale(): void
    {
        $user    = $this->requireUser();
        $userId  = (int)$user['id'];
        $queteId = (int)($_POST['quete_id'] ?? 0);
        $titre   = trim($_POST['titre'] ?? '');

        if (!$queteId || $titre === '') {
            Response::json(['success' => false, 'message' => 'Données invalides'], 400);
        }

        // Vérifier que tous les objets sont obtenus
        $stmt = $this->db->prepare("
            SELECT COUNT(qo.id) AS total,
                   SUM(CASE WHEN qoc.id IS NOT NULL THEN 1 ELSE 0 END) AS confirmes
            FROM quete_objets qo
            LEFT JOIN quete_objet_confirmes qoc ON qoc.quete_objet_id = qo.id AND qoc.user_id = ?
            WHERE qo.quete_id = ?
        ");
        $stmt->execute([$userId, $queteId]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$check || (int)$check['total'] !== (int)$check['confirmes'] || (int)$check['total'] === 0) {
            Response::json(['success' => false, 'message' => 'Tous les objets doivent être obtenus d\'abord'], 403);
        }

        $this->db->prepare("
            INSERT INTO quete_parcours_final (user_id, quete_id, titre, ville, distance_km)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $userId,
            $queteId,
            $titre,
            trim($_POST['ville'] ?? '') ?: null,
            !empty($_POST['distance_km']) ? (float)$_POST['distance_km'] : null,
        ]);

        $id = (int)$this->db->lastInsertId();
        Response::json(['success' => true, 'id' => $id, 'message' => 'Parcours final ajouté !']);
    }

    /* =========================================================
       POST /api/quetes/parcours-final/valider
       Body : parcours_final_id
    ========================================================= */
    public function validerParcoursFinal(): void
    {
        $user   = ApiAuth::requireAuth();
        $userId = (int)$user['id'];
        $id     = (int)($_POST['parcours_final_id'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT * FROM quete_parcours_final WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $userId]);
        $pf = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pf) {
            Response::json(['success' => false, 'message' => 'Parcours final introuvable'], 404);
        }

        $this->db->prepare("
            UPDATE quete_parcours_final SET date_validation = NOW() WHERE id = ? AND user_id = ?
        ")->execute([$id, $userId]);

        Response::json(['success' => true, 'message' => 'Parcours final validé !']);
    }

    /* =========================================================
       POST /api/quetes/parcours-final/supprimer
    ========================================================= */
    public function supprimerParcoursFinal(): void
    {
        $user   = $this->requireUser();
        $userId = (int)$user['id'];
        $id     = (int)($_POST['parcours_final_id'] ?? 0);

        $this->db->prepare("
            DELETE FROM quete_parcours_final WHERE id = ? AND user_id = ?
        ")->execute([$id, $userId]);

        Response::json(['success' => true, 'message' => 'Supprimé.']);
    }

    /* =========================================================
       Helpers
    ========================================================= */
    /* =========================================================
       Helper : authentification web (session) ou API (token)
    ========================================================= */
    private function requireUser(): array
    {
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/api/');
        if ($isApi) {
            return ApiAuth::requireAuth();
        }
        Auth::check();
        return $_SESSION['user'];
    }

    private function getParcoursFinaux(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM quete_parcours_final WHERE user_id = ? ORDER BY created_at ASC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['quete_id']][] = $r;
        }
        return $result;
    }
}