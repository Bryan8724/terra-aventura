<?php

namespace Models;

use Core\Database;
use PDO;
use Throwable;

class Maintenance
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* =========================================================
       PARCOURS EN MAINTENANCE
    ========================================================= */

    /**
     * ✅ FIX : méthode utilisée dans ParcoursController::valider() mais absente
     * Vérifie si un parcours est actuellement en maintenance
     */
    public function isInMaintenance(int $parcoursId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM parcours_maintenance
            WHERE parcours_id = ?
            LIMIT 1
        ");
        $stmt->execute([$parcoursId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * 🔢 Nombre total de parcours en maintenance
     */
    public function countParcours(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM parcours_maintenance
        ");

        return (int) $stmt->fetchColumn();
    }

    /**
     * 📦 Parcours paginés
     */
    public function getParcoursPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                p.id,
                p.titre,
                p.ville,
                p.departement_code,
                p.niveau,
                p.terrain,
                p.duree,
                p.distance_km,
                p.poiz_id,
                z.nom  AS poiz_nom,
                z.logo AS poiz_logo
            FROM parcours_maintenance pm
            INNER JOIN parcours p ON p.id = pm.parcours_id
            LEFT JOIN poiz z ON z.id = p.poiz_id
            ORDER BY p.titre ASC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 🔁 (Compatibilité ancienne méthode si besoin)
     */
    public function getAll(): array
    {
        return $this->getParcoursPaginated(9999, 0);
    }

    /* =========================================================
       HISTORIQUE
    ========================================================= */

    /**
     * 🔢 Nombre total d'entrées historiques
     */
    public function countHistory(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM maintenance_history
        ");

        return (int) $stmt->fetchColumn();
    }

    /**
     * 📦 Historique paginé
     */
    public function getHistoryPaginated(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                h.id,
                h.updated_at,
                h.snapshot,
                u.username
            FROM maintenance_history h
            JOIN users u ON u.id = h.updated_by
            ORDER BY h.updated_at DESC
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Snapshot spécifique
     */
    public function getHistorySnapshot(int $historyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, updated_at, updated_by, snapshot
            FROM maintenance_history
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$historyId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* =========================================================
       META
    ========================================================= */

    public function getMeta(): ?array
    {
        $stmt = $this->db->query("
            SELECT 
                m.updated_at,
                u.username
            FROM maintenance_meta m
            JOIN users u ON u.id = m.updated_by
            LIMIT 1
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Mapping ID → TITRE
     */
    public function getParcoursTitles(): array
    {
        $stmt = $this->db->query("
            SELECT id, titre
            FROM parcours
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];

        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row['titre'];
        }

        return $map;
    }

    /* =========================================================
       UPDATE MAINTENANCE
    ========================================================= */

    public function update(array $ids, int $userId): void
    {
        try {

            $this->db->beginTransaction();

            /* --------------------------
               Nettoyage sécurité
            -------------------------- */
            $ids = array_unique(
                array_filter(
                    array_map('intval', $ids),
                    fn($id) => $id > 0
                )
            );

            /* --------------------------
               Snapshot AVANT modif
            -------------------------- */
            $stmtSnapshot = $this->db->query("
                SELECT parcours_id
                FROM parcours_maintenance
            ");

            $currentIds = array_map(
                'intval',
                $stmtSnapshot->fetchAll(PDO::FETCH_COLUMN)
            );

            $stmtHistory = $this->db->prepare("
                INSERT INTO maintenance_history (updated_at, updated_by, snapshot)
                VALUES (NOW(), ?, ?)
            ");

            $stmtHistory->execute([
                $userId,
                json_encode($currentIds)
            ]);

            /* --------------------------
               Vérification IDs valides
            -------------------------- */
            if (!empty($ids)) {

                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                $stmtCheck = $this->db->prepare("
                    SELECT id
                    FROM parcours
                    WHERE id IN ($placeholders)
                ");

                $stmtCheck->execute($ids);

                $ids = array_map(
                    'intval',
                    $stmtCheck->fetchAll(PDO::FETCH_COLUMN)
                );
            }

            /* --------------------------
               Reset maintenance
            -------------------------- */
            $this->db->exec("DELETE FROM parcours_maintenance");

            /* --------------------------
               Insert sécurisé
            -------------------------- */
            if (!empty($ids)) {

                $stmtInsert = $this->db->prepare("
                    INSERT INTO parcours_maintenance (parcours_id)
                    VALUES (?)
                ");

                foreach ($ids as $id) {
                    $stmtInsert->execute([$id]);
                }
            }

            /* --------------------------
               Mise à jour META
            -------------------------- */
            $stmtMetaCheck = $this->db->query("
                SELECT COUNT(*) FROM maintenance_meta
            ");

            $metaExists = (int) $stmtMetaCheck->fetchColumn() > 0;

            if ($metaExists) {

                $stmtMeta = $this->db->prepare("
                    UPDATE maintenance_meta
                    SET updated_at = NOW(),
                        updated_by = ?
                ");

                $stmtMeta->execute([$userId]);

            } else {

                $stmtMeta = $this->db->prepare("
                    INSERT INTO maintenance_meta (updated_at, updated_by)
                    VALUES (NOW(), ?)
                ");

                $stmtMeta->execute([$userId]);
            }

            /* --------------------------
               Nettoyage historique (max 50)
            -------------------------- */
            $this->db->exec("
                DELETE FROM maintenance_history
                WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id
                        FROM maintenance_history
                        ORDER BY updated_at DESC
                        LIMIT 50
                    ) AS t
                )
            ");

            $this->db->commit();

        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
