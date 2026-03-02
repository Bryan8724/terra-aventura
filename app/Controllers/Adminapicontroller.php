<?php

namespace Controllers;

use PDO;
use Core\ApiAuth;
use Core\Response;
use Core\Database;
use Models\Parcours;
use Models\Evenement;
use Models\Quete;

/**
 * AdminApiController — toutes les opérations CRUD admin pour l'app mobile
 * Routes : /api/admin/*
 */
class AdminApiController
{
    private PDO      $db;
    private Parcours $parcours;
    private Evenement $evenement;
    private Quete    $quete;

    public function __construct(?PDO $db = null)
    {
        $this->db        = $db ?? Database::getInstance();
        $this->parcours  = new Parcours($this->db);
        $this->evenement = new Evenement($this->db);
        $this->quete     = new Quete($this->db);
    }

    /* =========================================================
       AUTH HELPER
    ========================================================= */
    private function requireAdmin(): array
    {
        $user = ApiAuth::requireAuth();
        if (($user['role'] ?? '') !== 'admin') {
            Response::json(['success' => false, 'message' => 'Accès réservé aux administrateurs'], 403);
        }
        return $user;
    }

    /* =========================================================
       DONNÉES DE RÉFÉRENCE (poiz, départements)
    ========================================================= */
    public function refs(): void
    {
        $this->requireAdmin();

        $stmt = $this->db->query("SELECT id, nom, logo FROM poiz ORDER BY nom ASC");
        $poiz = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::json([
            'success'      => true,
            'poiz'         => $poiz,
            'departements' => $this->departements(),
        ]);
    }

    private function departements(): array
    {
        return [
            '16' => 'Charente',
            '17' => 'Charente-Maritime',
            '19' => 'Corrèze',
            '23' => 'Creuse',
            '24' => 'Dordogne',
            '33' => 'Gironde',
            '40' => 'Landes',
            '47' => 'Lot-et-Garonne',
            '64' => 'Pyrénées-Atlantiques',
            '79' => 'Deux-Sèvres',
            '86' => 'Vienne',
            '87' => 'Haute-Vienne',
        ];
    }

    /* =========================================================
       PARCOURS — CREATE / UPDATE
    ========================================================= */
    public function storeParcours(): void
    {
        $this->requireAdmin();

        $titre = trim($_POST['titre'] ?? '');
        $poizId = (int)($_POST['poiz_id'] ?? 0);

        if ($titre === '' || $poizId === 0) {
            Response::json(['success' => false, 'message' => 'Titre et POIZ obligatoires'], 400);
        }

        $depts = $this->departements();
        $deptCode = trim($_POST['departement_code'] ?? '');

        $id = $this->parcours->create([
            'poiz_id'          => $poizId,
            'titre'            => $titre,
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'niveau'           => (int)($_POST['niveau'] ?? 3),
            'terrain'          => (int)($_POST['terrain'] ?? 3),
            'duree'            => trim($_POST['duree'] ?? ''),
            'distance_km'      => (float)($_POST['distance_km'] ?? 0),
            'date_debut'       => trim($_POST['date_debut'] ?? '') ?: null,
            'date_fin'         => trim($_POST['date_fin'] ?? '') ?: null,
        ]);

        Response::json(['success' => true, 'id' => $id, 'message' => 'Parcours créé'], 201);
    }

    public function updateParcours(): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) Response::json(['success' => false, 'message' => 'ID manquant'], 400);

        $depts    = $this->departements();
        $deptCode = trim($_POST['departement_code'] ?? '');

        $this->parcours->update($id, [
            'poiz_id'          => (int)($_POST['poiz_id'] ?? 0),
            'titre'            => trim($_POST['titre'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'niveau'           => (int)($_POST['niveau'] ?? 3),
            'terrain'          => (int)($_POST['terrain'] ?? 3),
            'duree'            => trim($_POST['duree'] ?? ''),
            'distance_km'      => (float)($_POST['distance_km'] ?? 0),
            'date_debut'       => trim($_POST['date_debut'] ?? '') ?: null,
            'date_fin'         => trim($_POST['date_fin'] ?? '') ?: null,
        ]);

        Response::json(['success' => true, 'message' => 'Parcours mis à jour']);
    }

    public function getParcours(): void
    {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) Response::json(['success' => false, 'message' => 'ID manquant'], 400);

        $p = $this->parcours->find($id);
        if (!$p) Response::json(['success' => false, 'message' => 'Introuvable'], 404);

        Response::json(['success' => true, 'data' => $p]);
    }

    /* =========================================================
       ZAMÉLA — CREATE / UPDATE  (poiz_id forcé = ZAMELA_POIZ_ID)
    ========================================================= */
    public function storeZamela(): void
    {
        $this->requireAdmin();

        $titre = trim($_POST['titre'] ?? '');
        if ($titre === '') Response::json(['success' => false, 'message' => 'Titre obligatoire'], 400);

        $depts    = $this->departements();
        $deptCode = trim($_POST['departement_code'] ?? '');

        $id = $this->parcours->create([
            'poiz_id'          => Parcours::ZAMELA_POIZ_ID,
            'titre'            => $titre,
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'niveau'           => (int)($_POST['niveau'] ?? 3),
            'terrain'          => (int)($_POST['terrain'] ?? 3),
            'duree'            => trim($_POST['duree'] ?? ''),
            'distance_km'      => (float)($_POST['distance_km'] ?? 0),
            'date_debut'       => trim($_POST['date_debut'] ?? '') ?: null,
            'date_fin'         => trim($_POST['date_fin'] ?? '') ?: null,
        ]);

        Response::json(['success' => true, 'id' => $id, 'message' => 'Zaméla créé'], 201);
    }

    public function updateZamela(): void
    {
        $this->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) Response::json(['success' => false, 'message' => 'ID manquant'], 400);

        $depts    = $this->departements();
        $deptCode = trim($_POST['departement_code'] ?? '');

        $this->parcours->update($id, [
            'poiz_id'          => Parcours::ZAMELA_POIZ_ID,
            'titre'            => trim($_POST['titre'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'niveau'           => (int)($_POST['niveau'] ?? 3),
            'terrain'          => (int)($_POST['terrain'] ?? 3),
            'duree'            => trim($_POST['duree'] ?? ''),
            'distance_km'      => (float)($_POST['distance_km'] ?? 0),
            'date_debut'       => trim($_POST['date_debut'] ?? '') ?: null,
            'date_fin'         => trim($_POST['date_fin'] ?? '') ?: null,
        ]);

        Response::json(['success' => true, 'message' => 'Zaméla mis à jour']);
    }

    /* =========================================================
       ÉVÉNEMENTS — CREATE / UPDATE
    ========================================================= */
    public function storeEvenement(): void
    {
        $this->requireAdmin();

        $nom = trim($_POST['nom'] ?? '');
        if ($nom === '') Response::json(['success' => false, 'message' => 'Nom obligatoire'], 400);

        $depts    = $this->departements();
        $deptCode = trim($_POST['departement_code'] ?? '');

        $id = $this->evenement->create([
            'nom'              => $nom,
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'date_debut'       => trim($_POST['date_debut'] ?? '') ?: null,
            'date_fin'         => trim($_POST['date_fin'] ?? '') ?: null,
            'image'            => null,
        ]);

        // Parcours associés (JSON)
        $parcoursList = json_decode(trim($_POST['parcours_json'] ?? '[]'), true) ?: [];
        foreach ($parcoursList as $p) {
            if (!empty($p['titre'])) $this->evenement->addParcours($id, $p);
        }

        Response::json(['success' => true, 'id' => $id, 'message' => 'Événement créé'], 201);
    }

    public function updateEvenement(): void
    {
        $this->requireAdmin();

        $id  = (int)($_POST['id'] ?? 0);
        $evt = $this->evenement->find($id);
        if (!$evt) Response::json(['success' => false, 'message' => 'Événement introuvable'], 404);

        $depts    = $this->departements();
        $deptCode = trim($_POST['departement_code'] ?? '');

        $this->evenement->update($id, [
            'nom'              => trim($_POST['nom'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'departement_code' => $deptCode,
            'departement_nom'  => $depts[$deptCode] ?? '',
            'date_debut'       => trim($_POST['date_debut'] ?? '') ?: null,
            'date_fin'         => trim($_POST['date_fin'] ?? '') ?: null,
            'image'            => $evt['image'],
        ]);

        // Parcours : reconstruire
        $parcoursList = json_decode(trim($_POST['parcours_json'] ?? '[]'), true) ?: [];
        $keepIds = array_filter(array_column($parcoursList, 'id'));

        if (!empty($keepIds)) {
            $in = implode(',', array_map('intval', $keepIds));
            $this->db->exec("DELETE FROM evenement_parcours WHERE evenement_id = $id AND id NOT IN ($in)");
        } else {
            $this->db->exec("DELETE FROM evenement_parcours WHERE evenement_id = $id");
        }

        foreach ($parcoursList as $p) {
            if (empty($p['titre'])) continue;
            if (!empty($p['id'])) $this->evenement->updateParcours((int)$p['id'], $p);
            else $this->evenement->addParcours($id, $p);
        }

        Response::json(['success' => true, 'message' => 'Événement mis à jour']);
    }

    public function getEvenement(): void
    {
        $this->requireAdmin();
        $id  = (int)($_GET['id'] ?? 0);
        $evt = $this->evenement->find($id);
        if (!$evt) Response::json(['success' => false, 'message' => 'Introuvable'], 404);

        // Inclure les parcours associés
        $stmt = $this->db->prepare("SELECT * FROM evenement_parcours WHERE evenement_id = ? ORDER BY id ASC");
        $stmt->execute([$id]);
        $evt['parcours'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::json(['success' => true, 'data' => $evt]);
    }

    /* =========================================================
       QUÊTES — délègue à AdminQueteController
    ========================================================= */
    public function storeQuete(): void
    {
        (new AdminQueteController($this->db))->store();
    }

    public function updateQuete(): void
    {
        (new AdminQueteController($this->db))->update();
    }

    public function getQuete(): void
    {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) Response::json(['success' => false, 'message' => 'ID manquant'], 400);

        $stmt = $this->db->prepare("SELECT * FROM quetes WHERE id = ?");
        $stmt->execute([$id]);
        $quete = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$quete) Response::json(['success' => false, 'message' => 'Introuvable'], 404);

        // Objets de la quête
        $stmt = $this->db->prepare("
            SELECT qo.id, qo.nom,
                   GROUP_CONCAT(qop.parcours_id ORDER BY qop.id SEPARATOR ',') AS parcours_ids
            FROM quete_objets qo
            LEFT JOIN quete_objet_parcours qop ON qop.quete_objet_id = qo.id
            WHERE qo.quete_id = ?
            GROUP BY qo.id
        ");
        $stmt->execute([$id]);
        $quete['objets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::json(['success' => true, 'data' => $quete]);
    }

    /* =========================================================
       SEARCH PARCOURS (pour quêtes)
    ========================================================= */
    public function searchParcours(): void
    {
        $this->requireAdmin();
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) Response::json(['success' => true, 'data' => []]);

        $stmt = $this->db->prepare("
            SELECT p.id, p.titre, p.ville, p.departement_code, po.nom AS poiz_nom
            FROM parcours p
            JOIN poiz po ON po.id = p.poiz_id
            WHERE (p.titre LIKE ? OR p.ville LIKE ?) AND p.archived = 0
            LIMIT 20
        ");
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);
        Response::json(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}