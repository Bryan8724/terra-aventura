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

            // Fuseau horaire MySQL
            $pdo->exec("SET time_zone = 'Europe/Paris'");

            return $pdo;

        } catch (PDOException $e) {

            self::handleError($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ FIX : Gestion erreur propre Web / API
    |    Avant : echo 'Erreur de connexion à la base de données.' (texte brut)
    |    Après : page HTML propre via ErrorPage, JSON si API
    |--------------------------------------------------------------------------
    */
    private static function handleError(PDOException $e): never
    {
        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod';

        $message = 'Impossible de se connecter à la base de données. Veuillez réessayer dans quelques instants.';

        // En mode API → JSON
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        if (str_starts_with($uri, '/api/')) {
            ErrorPage::json(500, 'Erreur de connexion à la base de données');
        }

        // En mode web → page HTML propre (avec détail en dev)
        ErrorPage::render(500, $message, $env === 'dev' ? $e : null);
    }
}