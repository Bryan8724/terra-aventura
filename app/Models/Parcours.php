<?php

namespace Models;

use Core\Database;
use PDO;

class Parcours
{
    private PDO $db;

    /** ID du POIZ Zaméla — parcours éphémères avec date_debut/date_fin */
    public const ZAMELA_POIZ_ID = 32;

    // ✅ FIX : accepte un PDO injecté (cohérent avec tous les autres modèles)
    //          ParcoursController passait $this->db mais le constructeur l'ignorait
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /* =========================
       LISTE + FILTRES + PAGINATION
    ========================= */
    public function getAllWithFilters(
        int $userId,
        array $filters = [],
        int $limit = 25,
        int $offset = 0
    ): array {

        $sql = "
            SELECT
                p.*,
                po.nom  AS poiz_nom,
                po.logo AS poiz_logo,
                CASE WHEN pe.parcours_id IS NULL THEN 0 ELSE 1 END AS effectue,
                CASE WHEN pm.parcours_id IS NULL THEN 0 ELSE 1 END AS en_maintenance
            FROM parcours p
            INNER JOIN poiz po ON po.id = p.poiz_id
            LEFT JOIN parcours_effectues pe
                ON pe.parcours_id = p.id
               AND pe.user_id = ?
            LEFT JOIN parcours_maintenance pm
                ON pm.parcours_id = p.id
            WHERE 1=1
        ";

        $params = [$userId];

        /* ===== EXCLUSION / INCLUSION ZAMÉLA ===== */
        if (!empty($filters['zamela_only'])) {
            $sql .= " AND p.poiz_id = " . self::ZAMELA_POIZ_ID;
        } else {
            // Onglet Parcours classique : on exclut Zaméla
            $sql .= " AND p.poiz_id != " . self::ZAMELA_POIZ_ID;
        }

        /* ===== FILTRE EFFECTUÉS ===== */
        if (!empty($filters['effectues'])) {
            $sql .= " AND pe.parcours_id IS NOT NULL";
        }

        /* ===== FILTRE DÉPARTEMENTS ===== */
        if (!empty($filters['departements']) && is_array($filters['departements'])) {
            $departements = array_filter($filters['departements']);
            if (!empty($departements)) {
                $in = implode(',', array_fill(0, count($departements), '?'));
                $sql .= " AND p.departement_code IN ($in)";
                $params = array_merge($params, $departements);
            }
        }

        /* ===== FILTRE POIZ ===== */
        if (!empty($filters['poiz_id'])) {
            $sql .= " AND p.poiz_id = ?";
            $params[] = $filters['poiz_id'];
        }

        /* ===== RECHERCHE ===== */
        if (!empty($filters['search'])) {
            $sql .= " AND (
                p.titre LIKE ?
                OR p.ville LIKE ?
                OR p.departement_code LIKE ?
            )";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        /* ===== TRI ===== */
        if (!empty($filters['zamela_only'])) {
            // Zaméla : trier par date de début croissante
            $sql .= " ORDER BY p.date_debut ASC, p.departement_code";
        } else {
            $sql .= " ORDER BY p.departement_code, p.created_at DESC";
        }

        /* ===== PAGINATION (FIX MARIA DB) ===== */
        $limit  = (int) $limit;
        $offset = (int) $offset;
        $sql .= " LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       COMPTE TOTAL POUR PAGINATION
    ========================= */
    public function countWithFilters(int $userId, array $filters = []): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM parcours p
            LEFT JOIN parcours_effectues pe
                ON pe.parcours_id = p.id
               AND pe.user_id = ?
            WHERE 1=1
        ";

        $params = [$userId];

        /* ===== EXCLUSION / INCLUSION ZAMÉLA ===== */
        if (!empty($filters['zamela_only'])) {
            $sql .= " AND p.poiz_id = " . self::ZAMELA_POIZ_ID;
        } else {
            $sql .= " AND p.poiz_id != " . self::ZAMELA_POIZ_ID;
        }

        if (!empty($filters['effectues'])) {
            $sql .= " AND pe.parcours_id IS NOT NULL";
        }

        if (!empty($filters['departements']) && is_array($filters['departements'])) {
            $departements = array_filter($filters['departements']);
            if (!empty($departements)) {
                $in = implode(',', array_fill(0, count($departements), '?'));
                $sql .= " AND p.departement_code IN ($in)";
                $params = array_merge($params, $departements);
            }
        }

        if (!empty($filters['poiz_id'])) {
            $sql .= " AND p.poiz_id = ?";
            $params[] = $filters['poiz_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (
                p.titre LIKE ?
                OR p.ville LIKE ?
                OR p.departement_code LIKE ?
            )";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /* =========================
       CRUD ADMIN
    ========================= */
    public function create(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO parcours
            (poiz_id, titre, ville, departement_code, departement_nom,
             niveau, terrain, duree, distance_km, date_debut, date_fin)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['poiz_id'],
            $data['titre'],
            $data['ville'],
            $data['departement_code'],
            $data['departement_nom'],
            $data['niveau'],
            $data['terrain'],
            $data['duree'],
            $data['distance_km'],
            $data['date_debut'] ?: null,
            $data['date_fin']   ?: null,
        ]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM parcours WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE parcours SET
                poiz_id = ?, titre = ?, ville = ?, departement_code = ?, departement_nom = ?,
                niveau = ?, terrain = ?, duree = ?, distance_km = ?,
                date_debut = ?, date_fin = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['poiz_id'],
            $data['titre'],
            $data['ville'],
            $data['departement_code'],
            $data['departement_nom'],
            $data['niveau'],
            $data['terrain'],
            $data['duree'],
            $data['distance_km'],
            $data['date_debut'] ?: null,
            $data['date_fin']   ?: null,
            $id,
        ]);
    }

    public function canDelete(int $id): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM parcours_effectues
            WHERE parcours_id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);

        return !$stmt->fetchColumn();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM parcours WHERE id = ?");
        $stmt->execute([$id]);
    }

    /* =========================
       VALIDATION UTILISATEUR
    ========================= */
    private function alreadyValidated(int $userId, int $parcoursId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM parcours_effectues
            WHERE user_id = ?
              AND parcours_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId, $parcoursId]);

        return (bool) $stmt->fetchColumn();
    }

    public function validateForUser(
        int $userId,
        int $parcoursId,
        ?string $date,
        ?string $heure,
        int $badges
    ): void {
        if ($this->alreadyValidated($userId, $parcoursId)) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO parcours_effectues
            (user_id, parcours_id, date_validation, badges_recuperes)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $parcoursId,
            $date . ' ' . $heure,
            $badges
        ]);
    }

    /* =========================
       DÉTAIL PARCOURS EFFECTUÉ
    ========================= */
    public function getEffectueDetail(int $userId, int $parcoursId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.titre,
                p.ville,
                p.departement_code,
                po.nom  AS poiz_nom,
                po.logo AS poiz_logo,
                pe.date_validation,
                pe.badges_recuperes
            FROM parcours_effectues pe
            INNER JOIN parcours p ON p.id = pe.parcours_id
            INNER JOIN poiz po ON po.id = p.poiz_id
            WHERE pe.user_id = ?
              AND pe.parcours_id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId, $parcoursId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}