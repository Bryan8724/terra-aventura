<?php

namespace Models;

use PDO;

class Stock
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Récupère tout le stock de l'utilisateur (tous les POIZ actifs + quantité) */
    public function getStockUtilisateur(int $userId): array
    {
        $sql = "
            SELECT
                p.id,
                p.nom,
                p.logo,
                p.theme,
                COALESCE(upb.quantite, 0) AS quantite
            FROM poiz p
            LEFT JOIN user_poiz_badges upb
                ON upb.poiz_id = p.id AND upb.user_id = :user_id
            WHERE p.actif = 1
            ORDER BY p.nom ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Met à jour la quantité (écrase) */
    public function setQuantite(int $userId, int $poizId, int $quantite): void
    {
        $quantite = max(0, $quantite);

        $sql = "
            INSERT INTO user_poiz_badges (user_id, poiz_id, quantite)
            VALUES (:user_id, :poiz_id, :quantite)
            ON DUPLICATE KEY UPDATE quantite = :quantite
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id'  => $userId,
            'poiz_id'  => $poizId,
            'quantite' => $quantite,
        ]);
    }

    /** Ajuste la quantité (+n ou -n) */
    public function ajusterQuantite(int $userId, int $poizId, int $delta): int
    {
        $sql = "
            INSERT INTO user_poiz_badges (user_id, poiz_id, quantite)
            VALUES (:user_id, :poiz_id, GREATEST(0, :delta))
            ON DUPLICATE KEY UPDATE quantite = GREATEST(0, quantite + :delta2)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'poiz_id' => $poizId,
            'delta'   => $delta,
            'delta2'  => $delta,
        ]);

        // Retourner la nouvelle valeur
        $stmt2 = $this->db->prepare("SELECT quantite FROM user_poiz_badges WHERE user_id=:u AND poiz_id=:p");
        $stmt2->execute(['u' => $userId, 'p' => $poizId]);
        return (int)($stmt2->fetchColumn() ?? 0);
    }

    /** Total de badges en stock */
    public function getTotalBadges(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantite),0) FROM user_poiz_badges WHERE user_id=:u");
        $stmt->execute(['u' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
