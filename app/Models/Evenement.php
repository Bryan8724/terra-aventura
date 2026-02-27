<?php

namespace Models;

use PDO;

class Evenement
{
    public function __construct(private PDO $db) {}

    /* =====================================================
       LISTE + FILTRES
    ===================================================== */
    public function getAll(int $userId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [':uid' => $userId];

        if (!empty($filters['search'])) {
            $where[]          = '(e.nom LIKE :search OR e.ville LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['departement'])) {
            $where[]             = 'e.departement_code = :dept';
            $params[':dept']     = $filters['departement'];
        }
        if (!empty($filters['effectues'])) {
            $where[] = 'ee.id IS NOT NULL';
        }

        $sql = "
            SELECT e.*,
                   IF(ee.id IS NOT NULL, 1, 0) AS effectue,
                   ee.date_validation           AS date_participation,
                   (SELECT COUNT(*) FROM evenement_parcours ep2 WHERE ep2.evenement_id = e.id) AS nb_parcours,
                   (SELECT COUNT(*) FROM evenement_parcours_effectues epe2
                     INNER JOIN evenement_parcours ep3 ON ep3.id = epe2.evenement_parcours_id
                    WHERE ep3.evenement_id = e.id AND epe2.user_id = :uid2) AS nb_parcours_faits
            FROM evenements e
            LEFT JOIN evenement_effectues ee ON ee.evenement_id = e.id AND ee.user_id = :uid
            WHERE " . implode(' AND ', $where) . "
            ORDER BY e.date_debut DESC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid',    $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2',   $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim',    $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off',    $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            if ($k === ':uid') continue;
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       TROUVER PAR ID (avec ses parcours)
    ===================================================== */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM evenements WHERE id = ?");
        $stmt->execute([$id]);
        $evt = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$evt) return null;

        $stmt2 = $this->db->prepare("SELECT * FROM evenement_parcours WHERE evenement_id = ? ORDER BY id ASC");
        $stmt2->execute([$id]);
        $evt['parcours'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        return $evt;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $evt = $this->find($id);
        if (!$evt) return null;

        $stmt = $this->db->prepare("SELECT * FROM evenement_effectues WHERE evenement_id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $evt['effectue'] = $stmt->fetch(PDO::FETCH_ASSOC);

        foreach ($evt['parcours'] as &$p) {
            $s = $this->db->prepare("SELECT * FROM evenement_parcours_effectues WHERE evenement_parcours_id = ? AND user_id = ?");
            $s->execute([$p['id'], $userId]);
            $p['effectue'] = (bool)$s->fetchColumn();
        }

        return $evt;
    }

    /* =====================================================
       CRÉER
    ===================================================== */
    public function create(array $data): int
    {
        $this->db->prepare("
            INSERT INTO evenements (nom, ville, departement_code, departement_nom, date_debut, date_fin, image)
            VALUES (:nom, :ville, :departement_code, :departement_nom, :date_debut, :date_fin, :image)
        ")->execute([
            ':nom'              => $data['nom'],
            ':ville'            => $data['ville'],
            ':departement_code' => $data['departement_code'],
            ':departement_nom'  => $data['departement_nom'],
            ':date_debut'       => $data['date_debut'],
            ':date_fin'         => $data['date_fin'],
            ':image'            => $data['image'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /* =====================================================
       METTRE À JOUR
    ===================================================== */
    public function update(int $id, array $data): void
    {
        $this->db->prepare("
            UPDATE evenements SET
                nom              = :nom,
                ville            = :ville,
                departement_code = :departement_code,
                departement_nom  = :departement_nom,
                date_debut       = :date_debut,
                date_fin         = :date_fin,
                image            = :image
            WHERE id = :id
        ")->execute([
            ':nom'              => $data['nom'],
            ':ville'            => $data['ville'],
            ':departement_code' => $data['departement_code'],
            ':departement_nom'  => $data['departement_nom'],
            ':date_debut'       => $data['date_debut'],
            ':date_fin'         => $data['date_fin'],
            ':image'            => $data['image'] ?: null,
            ':id'               => $id,
        ]);
    }

    /* =====================================================
       SUPPRIMER (cascade sur parcours + effectués)
    ===================================================== */
    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM evenements WHERE id = ?")->execute([$id]);
    }

    /* =====================================================
       PARCOURS D'ÉVÉNEMENT — CRUD
    ===================================================== */
    public function addParcours(int $evenementId, array $data): int
    {
        $this->db->prepare("
            INSERT INTO evenement_parcours (evenement_id, titre, niveau, terrain, duree, distance_km)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $evenementId,
            $data['titre'],
            $data['niveau'] ?? 3,
            $data['terrain'] ?? 3,
            $data['duree'] ?: null,
            $data['distance_km'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateParcours(int $parcoursId, array $data): void
    {
        $this->db->prepare("
            UPDATE evenement_parcours SET titre = ?, niveau = ?, terrain = ?, duree = ?, distance_km = ?
            WHERE id = ?
        ")->execute([
            $data['titre'],
            $data['niveau'] ?? 3,
            $data['terrain'] ?? 3,
            $data['duree'] ?: null,
            $data['distance_km'] ?: null,
            $parcoursId,
        ]);
    }

    public function deleteParcours(int $parcoursId): void
    {
        $this->db->prepare("DELETE FROM evenement_parcours WHERE id = ?")->execute([$parcoursId]);
    }

    /* =====================================================
       VALIDATION ÉVÉNEMENT
    ===================================================== */
    public function validerEvenement(int $userId, int $evenementId, ?string $date): void
    {
        $this->db->prepare("
            INSERT IGNORE INTO evenement_effectues (user_id, evenement_id, date_validation)
            VALUES (?, ?, ?)
        ")->execute([$userId, $evenementId, $date ?: null]);
    }

    public function resetEvenement(int $userId, int $evenementId): void
    {
        $this->db->prepare("DELETE FROM evenement_effectues WHERE user_id = ? AND evenement_id = ?")
                 ->execute([$userId, $evenementId]);
    }

    /* =====================================================
       VALIDATION PARCOURS D'ÉVÉNEMENT
    ===================================================== */
    public function validerParcours(int $userId, int $epId, ?string $date): void
    {
        $this->db->prepare("
            INSERT IGNORE INTO evenement_parcours_effectues (user_id, evenement_parcours_id, date_validation)
            VALUES (?, ?, ?)
        ")->execute([$userId, $epId, $date ?: null]);
    }

    public function resetParcours(int $userId, int $epId): void
    {
        $this->db->prepare("DELETE FROM evenement_parcours_effectues WHERE user_id = ? AND evenement_parcours_id = ?")
                 ->execute([$userId, $epId]);
    }

    /* =====================================================
       COMPTEURS DASHBOARD / STATS
    ===================================================== */
    public function countTotal(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM evenements")->fetchColumn();
    }

    public function countEffectuesByUser(int $userId): int
    {
        $s = $this->db->prepare("SELECT COUNT(*) FROM evenement_effectues WHERE user_id = ?");
        $s->execute([$userId]);
        return (int)$s->fetchColumn();
    }
}
