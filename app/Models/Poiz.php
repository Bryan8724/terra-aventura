<?php

namespace Models;

use PDO;

class Poiz
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(bool $onlyActive = true): array
    {
        $where = $onlyActive ? "WHERE p.actif = 1" : "";
        $sql = "
            SELECT p.*,
                   COUNT(pa.id) AS nb_parcours
            FROM poiz p
            LEFT JOIN parcours pa ON pa.poiz_id = p.id
            $where
            GROUP BY p.id
            ORDER BY p.nom ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM poiz WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO poiz (nom, theme, logo)
            VALUES (:nom, :theme, :logo)
        ");
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        $stmt = $this->db->prepare("
            UPDATE poiz SET
                nom = :nom,
                theme = :theme,
                logo = :logo
            WHERE id = :id
        ");
        $stmt->execute($data);
    }
}
