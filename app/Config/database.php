<?php

/*
|--------------------------------------------------------------------------
| Configuration Base de Données
|--------------------------------------------------------------------------
|
| Les variables peuvent être définies :
| - en variable d'environnement (Docker, serveur)
| - ou fallback sur valeurs par défaut
|
*/

return [

    'host' => getenv('DB_HOST') ?: 'terra-aventura-db',

    'dbname' => getenv('DB_NAME') ?: 'terra_aventura',

    'user' => getenv('DB_USER') ?: 'terra',

    'password' => getenv('DB_PASSWORD') ?: 'terra',

    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',

];
