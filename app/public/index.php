<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ENVIRONMENT
|--------------------------------------------------------------------------
*/

$env = 'dev';

/*
|--------------------------------------------------------------------------
| DEBUG MODE
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
*/

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

if (str_starts_with($requestPath, '/api/')) {

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');

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
| ✅ FIX : les erreurs non gérées affichent désormais une page HTML propre
|          via ErrorPage::render() au lieu de "Une erreur est survenue."
|          En mode API → JSON structuré. En mode dev → trace complète affichée.
*/

use Core\ErrorPage;

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {

    // Respecter l'opérateur @ (suppression d'erreurs)
    if (error_reporting() === 0) {
        return false;
    }

    // Convertir les erreurs PHP en exceptions pour qu'elles remontent
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (\Throwable $e) use ($env): void {

    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

    // ✅ FIX : version API → JSON structuré avec détail en dev
    if (str_starts_with($uri, '/api/')) {

        while (ob_get_level() > 0) ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');

        $payload = ['success' => false, 'message' => 'Erreur serveur interne'];

        if ($env === 'dev') {
            $payload['debug'] = [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ];
        }

        echo json_encode($payload);
        exit;
    }

    // ✅ FIX : version web → page HTML propre au lieu de "Une erreur est survenue."
    ErrorPage::render(500, null, $e);
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