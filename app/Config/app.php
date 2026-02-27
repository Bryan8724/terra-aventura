<?php

/*
|--------------------------------------------------------------------------
| Environnement
|--------------------------------------------------------------------------
|
| APP_ENV   = dev | prod
| APP_DEBUG = true | false
|
| ✅ FIX : valeur par défaut harmonisée avec index.php ('prod' au lieu de 'local')
|          Le docker-compose définit APP_ENV=dev explicitement.
|          Sans variable d'env → comportement production (sécurisé par défaut).
|
*/

$env = getenv('APP_ENV') ?: 'prod';

$debugEnv = getenv('APP_DEBUG');
$debug = $debugEnv !== false
    ? filter_var($debugEnv, FILTER_VALIDATE_BOOLEAN)
    : ($env === 'dev');


/*
|--------------------------------------------------------------------------
| Constantes principales
|--------------------------------------------------------------------------
*/

define('BASE_PATH', dirname(__DIR__));
define('VIEW_PATH', BASE_PATH . '/Views');
define('STORAGE_PATH', BASE_PATH . '/Storage');

define('APP_ENV', $env);
define('APP_DEBUG', $debug);


/*
|--------------------------------------------------------------------------
| Configuration des erreurs
|--------------------------------------------------------------------------
*/

if (APP_DEBUG) {

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

} else {

    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}


/*
|--------------------------------------------------------------------------
| Sécurité Session
|--------------------------------------------------------------------------
|
| ⚠️ IMPORTANT :
| Ces paramètres doivent être définis AVANT session_start()
| (dans public/index.php la session démarre après ce fichier)
|
*/

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    // Empêche l'ID session dans l'URL
    ini_set('session.use_trans_sid', '0');
}