<?php

namespace Core;

use PDO;
use PDOStatement;

abstract class Model
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /*
    |--------------------------------------------------------------------------
    | Exécution requête préparée
    |--------------------------------------------------------------------------
    */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch all simplifié
    |--------------------------------------------------------------------------
    */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch one simplifié
    |--------------------------------------------------------------------------
    */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Last insert ID
    |--------------------------------------------------------------------------
    */
    protected function lastInsertId(): string|false
    {
        return $this->db->lastInsertId();
    }
}
