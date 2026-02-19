<?php

namespace Models;

use PDO;

class Quete
{
    public function __construct(private PDO $db) {}

    /* =====================================================
       QUÊTES / OBJETS / PARCOURS
       ✔ User  : voit ses objets obtenus ou non
       ✔ Admin : voit tout
       ✔ Objet obtenu = AU MOINS un parcours validé
    ===================================================== */
    public function getAllCompletes(?int $userId = null): array
    {
        $sql = "
            SELECT
                q.id    AS quete_id,
                q.nom   AS quete_nom,
                q.saison,

                qo.id   AS objet_id,
                qo.nom  AS objet_nom,

                p.id               AS parcours_id,
                p.titre            AS parcours_nom,
                p.ville,
                p.departement_code AS departement,

                po.logo            AS poiz_logo,
        ";

        // 👤 USER : objet obtenu si AU MOINS un parcours validé
        if ($userId !== null) {
            $sql .= "
                EXISTS (
                    SELECT 1
                    FROM quete_objet_parcours qop2
                    JOIN parcours_effectues pe2
                        ON pe2.parcours_id = qop2.parcours_id
                       AND pe2.user_id = :userId
                       AND pe2.date_validation IS NOT NULL
                    WHERE qop2.quete_objet_id = qo.id
                ) AS objet_obtenu
            ";
        }
        // 👑 ADMIN
        else {
            $sql .= "0 AS objet_obtenu";
        }

        $sql .= "
            FROM quetes q

            JOIN quete_objets qo
                ON qo.quete_id = q.id

            JOIN quete_objet_parcours qop
                ON qop.quete_objet_id = qo.id

            JOIN parcours p
                ON p.id = qop.parcours_id

            JOIN poiz po
                ON po.id = p.poiz_id

            ORDER BY q.nom, qo.nom, p.titre
        ";

        $stmt = $this->db->prepare($sql);

        if ($userId !== null) {
            $stmt->execute(['userId' => $userId]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       QUÊTE SIMPLE
    ===================================================== */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nom, saison FROM quetes WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* =====================================================
       OBJETS + PARCOURS D’UNE QUÊTE (ADMIN)
    ===================================================== */
    public function getObjetsWithParcours(int $queteId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                qo.id AS objet_id,
                qo.nom AS objet_nom,

                p.id AS parcours_id,
                p.titre,
                p.ville,
                p.departement_code,
                po.logo

            FROM quete_objets qo
            LEFT JOIN quete_objet_parcours qop
                ON qop.quete_objet_id = qo.id
            LEFT JOIN parcours p
                ON p.id = qop.parcours_id
            LEFT JOIN poiz po
                ON po.id = p.poiz_id

            WHERE qo.quete_id = ?
            ORDER BY qo.id, p.titre
        ");

        $stmt->execute([$queteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =====================================================
       UPDATE QUÊTE
    ===================================================== */
    public function update(int $id, string $nom, ?string $saison): void
    {
        $this->db->prepare(
            "UPDATE quetes SET nom = ?, saison = ? WHERE id = ?"
        )->execute([$nom, $saison, $id]);
    }

    /* =====================================================
       UPDATE OBJETS + PARCOURS
    ===================================================== */
    public function updateObjets(int $queteId, array $objets): void
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM quete_objets WHERE quete_id = ?"
        );
        $stmt->execute([$queteId]);
        $existingIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $keepIds = [];

        foreach ($objets as $objet) {
            $objetId  = isset($objet['id']) ? (int) $objet['id'] : null;
            $nom      = trim((string) ($objet['nom'] ?? ''));
            $parcours = array_map('intval', $objet['parcours'] ?? []);

            if ($nom === '') {
                continue;
            }

            // ➕ Nouvel objet
            if (!$objetId) {
                $this->createObjet($queteId, $nom, $parcours);
                continue;
            }

            // ✏️ Objet existant
            $this->db->prepare(
                "UPDATE quete_objets SET nom = ? WHERE id = ?"
            )->execute([$nom, $objetId]);

            $this->syncParcours($objetId, $parcours);
            $keepIds[] = $objetId;
        }

        // 🗑 Suppression des objets supprimés
        foreach ($existingIds as $eid) {
            if (!in_array($eid, $keepIds, true)) {
                $this->deleteObjetCascade($eid);
            }
        }
    }

    /* =====================================================
       CRÉATION OBJET
    ===================================================== */
    private function createObjet(
        int $queteId,
        string $nom,
        array $parcours
    ): void {
        $this->db->prepare(
            "INSERT INTO quete_objets (quete_id, nom)
             VALUES (?, ?)"
        )->execute([$queteId, $nom]);

        $objetId = (int) $this->db->lastInsertId();

        if (!empty($parcours)) {
            $this->syncParcours($objetId, $parcours);
        }
    }

    /* =====================================================
       SYNC PARCOURS
    ===================================================== */
    private function syncParcours(int $objetId, array $parcoursIds): void
    {
        $this->db->prepare(
            "DELETE FROM quete_objet_parcours WHERE quete_objet_id = ?"
        )->execute([$objetId]);

        if (empty($parcoursIds)) {
            return;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO quete_objet_parcours
             (quete_objet_id, parcours_id)
             VALUES (?, ?)"
        );

        foreach (array_unique($parcoursIds) as $pid) {
            $stmt->execute([$objetId, $pid]);
        }
    }

    /* =====================================================
       🗑 SUPPRESSION OBJET (CASCADE)
    ===================================================== */
    private function deleteObjetCascade(int $objetId): void
    {
        $this->db->prepare(
            "DELETE FROM quete_objet_parcours WHERE quete_objet_id = ?"
        )->execute([$objetId]);

        $this->db->prepare(
            "DELETE FROM quete_objets WHERE id = ?"
        )->execute([$objetId]);
    }

    /* =====================================================
       🗑 SUPPRESSION QUÊTE (CASCADE COMPLÈTE)
    ===================================================== */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM quete_objets WHERE quete_id = ?"
        );
        $stmt->execute([$id]);
        $objetIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        foreach ($objetIds as $objetId) {
            $this->deleteObjetCascade($objetId);
        }

        $this->db->prepare(
            "DELETE FROM quetes WHERE id = ?"
        )->execute([$id]);
    }
}
