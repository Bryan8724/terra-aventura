<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ENVIRONMENT
|--------------------------------------------------------------------------
| ✅ FIX : harmonisé avec app.php — défaut 'prod' dans les deux fichiers
*/

$env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod';

/*
|--------------------------------------------------------------------------
| DEBUG MODE (DEV ONLY)
|--------------------------------------------------------------------------
*/

if ($env === 'dev') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

/*
|--------------------------------------------------------------------------
| HTTPS DETECTION (PROXY SAFE)
|--------------------------------------------------------------------------
*/

$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['SERVER_PORT'] ?? null) == 443
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

/*
|--------------------------------------------------------------------------
| CORS — APPLICATION MOBILE
|--------------------------------------------------------------------------
| ✅ AJOUT : les appels API depuis l'app mobile nécessitent des headers CORS.
|    On les applique uniquement aux routes /api/ pour ne pas exposer le site web.
*/

$requestUri  = $_SERVER['REQUEST_URI'] ?? '';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?? '';

if (str_starts_with($requestPath, '/api/')) {

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');

    // Réponse immédiate aux requêtes preflight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| SESSION CONFIG
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => $isHttps ? 'None' : 'Lax',
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| TIMEZONE
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Europe/Paris');

/*
|--------------------------------------------------------------------------
| ROOT PATH
|--------------------------------------------------------------------------
*/

define('ROOT_PATH', dirname(__DIR__));

/*
|--------------------------------------------------------------------------
| LOAD CONFIG + AUTOLOADER
|--------------------------------------------------------------------------
*/

require_once ROOT_PATH . '/Config/app.php';
require_once ROOT_PATH . '/Core/Autoloader.php';

/*
|--------------------------------------------------------------------------
| GLOBAL ERROR HANDLING
|--------------------------------------------------------------------------
*/

set_error_handler(function ($severity, $message, $file, $line) use ($env) {

    // Respecter l'opérateur @ (suppression d'erreurs)
    if (error_reporting() === 0) {
        return false;
    }

    if ($env === 'dev') {
        echo "<pre style='background:#111;color:#ff6b6b;padding:20px'>";
        echo "⚠ PHP ERROR\n\n";
        echo "Message : " . $message . "\n\n";
        echo "File    : " . $file . "\n";
        echo "Line    : " . $line . "\n";
        echo "</pre>";
        exit;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e) use ($env) {

    http_response_code(500);

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

    if ($env === 'dev') {

        echo "<pre style='background:#111;color:#ff6b6b;padding:20px'>";
        echo "💥 EXCEPTION\n\n";
        echo "Message : " . $e->getMessage() . "\n\n";
        echo "File    : " . $e->getFile() . "\n";
        echo "Line    : " . $e->getLine() . "\n\n";
        echo "Trace :\n" . $e->getTraceAsString();
        echo "</pre>";
        exit;
    }

    if (str_starts_with($uri, '/api/')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur serveur'
        ]);
        exit;
    }

    echo 'Une erreur est survenue.';
    exit;
});

/*
|--------------------------------------------------------------------------
| INIT DB + ROUTER
|--------------------------------------------------------------------------
*/

use Core\Database;
use Core\Router;

$db     = Database::getInstance();
$router = new Router($db);

/*
|--------------------------------------------------------------------------
| LOAD ROUTES
|--------------------------------------------------------------------------
*/

require_once ROOT_PATH . '/Routes/web.php';

/*
|--------------------------------------------------------------------------
| DISPATCH
|--------------------------------------------------------------------------
*/

$router->dispatch();