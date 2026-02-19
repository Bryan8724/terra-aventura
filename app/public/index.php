<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
|--------------------------------------------------------------------------
| Détection HTTPS réelle (proxy compatible)
|--------------------------------------------------------------------------
*/
$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? null) == 443
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

/*
|--------------------------------------------------------------------------
| Configuration Session Sécurisée
|--------------------------------------------------------------------------
*/
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => $isHttps ? 'None' : 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/
date_default_timezone_set('Europe/Paris');

/*
|--------------------------------------------------------------------------
| Constantes globales
|--------------------------------------------------------------------------
*/
define('ROOT_PATH', dirname(__DIR__));

/*
|--------------------------------------------------------------------------
| Chargement Config & Autoloader
|--------------------------------------------------------------------------
*/
require_once ROOT_PATH . '/Config/app.php';
require_once ROOT_PATH . '/Core/Autoloader.php';

/*
|--------------------------------------------------------------------------
| Gestion erreurs globale (Web + API)
|--------------------------------------------------------------------------
*/
set_exception_handler(function (Throwable $e) {

    http_response_code(500);

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

    if (str_starts_with($uri, '/api/')) {

        header('Content-Type: application/json');

        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur'
        ]);

        return;
    }

    echo 'Une erreur est survenue.';
});

set_error_handler(function ($severity, $message, $file, $line) {
    echo "<pre>";
    echo "ERREUR PHP:\n";
    echo $message . "\n";
    echo "Fichier: " . $file . "\n";
    echo "Ligne: " . $line . "\n";
    echo "</pre>";
    exit;
});


/*
|--------------------------------------------------------------------------
| Initialisation DB & Router
|--------------------------------------------------------------------------
*/
use Core\Database;
use Core\Router;

$db = Database::getInstance();
$router = new Router($db);

/*
|--------------------------------------------------------------------------
| Chargement Routes
|--------------------------------------------------------------------------
*/
require_once ROOT_PATH . '/Routes/web.php';

/*
|--------------------------------------------------------------------------
| Dispatch
|--------------------------------------------------------------------------
*/
$router->dispatch();
