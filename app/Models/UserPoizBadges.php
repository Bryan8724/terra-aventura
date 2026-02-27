<?php

namespace Models;

use PDO;

class UserPoizBadges
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function addBadge(int $userId, int $poizId): void
    {
        $sql = "
            INSERT INTO user_poiz_badges (user_id, poiz_id, quantite)
            VALUES (:user_id, :poiz_id, 1)
            ON DUPLICATE KEY UPDATE quantite = quantite + 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'poiz_id' => $poizId
        ]);
    }

    public function getUserBadges(int $userId): array
    {
        $sql = "
            SELECT p.nom, p.logo, upb.quantite
            FROM user_poiz_badges upb
            JOIN poiz p ON p.id = upb.poiz_id
            WHERE upb.user_id = :user
            ORDER BY p.nom ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
