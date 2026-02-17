<?php

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /*
    |--------------------------------------------------------------------------
    | Instance unique PDO
    |--------------------------------------------------------------------------
    */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /*
    |--------------------------------------------------------------------------
    | Création connexion
    |--------------------------------------------------------------------------
    */
    private static function createConnection(): PDO
    {
        $config = require dirname(__DIR__) . '/Config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        try {

            $pdo = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

            // 🔥 Fuseau horaire MySQL
            $pdo->exec("SET time_zone = 'Europe/Paris'");

            return $pdo;

        } catch (PDOException $e) {

            self::handleError($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestion erreur propre Web / API
    |--------------------------------------------------------------------------
    */
    private static function handleError(PDOException $e): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

        http_response_code(500);

        if (str_starts_with($uri, '/api/')) {

            header('Content-Type: application/json');

            echo json_encode([
                'success' => false,
                'message' => 'Erreur de connexion base de données'
            ]);

        } else {

            echo 'Erreur de connexion à la base de données.';
        }

        exit;
    }
}
