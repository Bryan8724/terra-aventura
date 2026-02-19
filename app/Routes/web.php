<?php

use Core\Router;

/** @var Router $router */

/*
|--------------------------------------------------------------------------
| WEB ROUTES (SITE CLASSIQUE)
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| AUTH / DASHBOARD
|--------------------------------------------------------------------------
*/

$router->get('/', ['DashboardController', 'index']);

$router->get('/login', ['AuthController', 'loginForm']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);

$router->get('/forgot-password', ['AuthController', 'forgotPassword']);
$router->post('/forgot-password', ['AuthController', 'forgotPassword']);


/*
|--------------------------------------------------------------------------
| DÉPLOIEMENT (DEV UNIQUEMENT)
|--------------------------------------------------------------------------
*/

$router->post('/deploy', ['DeployController', 'deploy']);
$router->get('/deploy/log', ['DeployController', 'log']);


/*
|--------------------------------------------------------------------------
| DÉPLOIEMENT – STATUS (UTILISÉ PAR DEV POUR INTERROGER PROD)
|--------------------------------------------------------------------------
| ⚠️ Requiert DeployStatusController
*/

$router->get('/deploy-status', ['DeployStatusController', 'index']);


/*
|--------------------------------------------------------------------------
| PARCOURS
|--------------------------------------------------------------------------
*/

$router->get('/parcours', ['ParcoursController', 'index']);
$router->get('/parcours/create', ['ParcoursController', 'create']);
$router->post('/parcours/store', ['ParcoursController', 'store']);
$router->get('/parcours/edit', ['ParcoursController', 'edit']);
$router->post('/parcours/update', ['ParcoursController', 'update']);
$router->post('/parcours/delete', ['ParcoursController', 'delete']);
$router->post('/parcours/valider', ['ParcoursController', 'valider']);
$router->post('/parcours/reset', ['ParcoursController', 'reset']);
$router->get('/parcours/search', ['ParcoursController', 'search']);


/*
|--------------------------------------------------------------------------
| QUÊTES UTILISATEUR
|--------------------------------------------------------------------------
*/

$router->get('/quetes', ['QueteController', 'index']);


/*
|--------------------------------------------------------------------------
| QUÊTES ADMIN
|--------------------------------------------------------------------------
*/

$router->get('/admin/quetes', ['AdminQueteController', 'index']);
$router->get('/admin/quetes/create', ['AdminQueteController', 'create']);
$router->post('/admin/quetes/store', ['AdminQueteController', 'store']);
$router->get('/admin/quetes/edit', ['AdminQueteController', 'edit']);
$router->post('/admin/quetes/update', ['AdminQueteController', 'update']);
$router->post('/admin/quetes/delete', ['AdminQueteController', 'delete']);
$router->get('/admin/quetes/search-parcours', ['AdminQueteController', 'searchParcours']);


/*
|--------------------------------------------------------------------------
| POIZ
|--------------------------------------------------------------------------
*/

$router->get('/poiz', ['PoizController', 'index']);
$router->get('/poiz/create', ['PoizController', 'create']);
$router->post('/poiz/store', ['PoizController', 'store']);
$router->get('/poiz/edit', ['PoizController', 'edit']);
$router->post('/poiz/update', ['PoizController', 'update']);
$router->post('/poiz/delete', ['PoizController', 'delete']);


/*
|--------------------------------------------------------------------------
| ADMIN – MESSAGES
|--------------------------------------------------------------------------
*/

$router->get('/admin/messages', ['AdminMessageController', 'index']);
$router->post('/admin/messages/process', ['AdminMessageController', 'process']);


/*
|--------------------------------------------------------------------------
| ADMIN – USERS
|--------------------------------------------------------------------------
*/

$router->get('/admin/users', ['AdminUserController', 'index']);
$router->get('/admin/users/create', ['AdminUserController', 'create']);
$router->post('/admin/users/store', ['AdminUserController', 'store']);
$router->get('/admin/users/edit', ['AdminUserController', 'edit']);
$router->post('/admin/users/update', ['AdminUserController', 'update']);
$router->post('/admin/users/delete', ['AdminUserController', 'delete']);


/*
|--------------------------------------------------------------------------
| USER PROFILE
|--------------------------------------------------------------------------
*/

$router->get('/user/profile', ['UserController', 'editProfile']);
$router->post('/user/update-profile', ['UserUpdateProfileController', 'index']);


/*
|--------------------------------------------------------------------------
| MAINTENANCE
|--------------------------------------------------------------------------
*/

$router->get('/maintenance', ['MaintenanceController', 'index']);
$router->get('/maintenance/ajaxParcours', ['MaintenanceController', 'ajaxParcours']);
$router->get('/maintenance/ajaxHistoryDiff', ['MaintenanceController', 'ajaxHistoryDiff']);
$router->post('/maintenance/update', ['MaintenanceController', 'update']);
$router->post('/maintenance/restore', ['MaintenanceController', 'restore']);


/*
|--------------------------------------------------------------------------
| API ROUTES (APPLICATION MOBILE)
|--------------------------------------------------------------------------
*/

$router->post('/api/login', ['AuthController', 'login']);
$router->post('/api/logout', ['AuthController', 'logout']);

$router->get('/api/dashboard', ['DashboardController', 'index']);

$router->get('/api/parcours', ['ParcoursController', 'index']);
$router->get('/api/parcours/search', ['ParcoursController', 'search']);
$router->post('/api/parcours/valider', ['ParcoursController', 'valider']);
$router->post('/api/parcours/reset', ['ParcoursController', 'reset']);

$router->get('/api/quetes', ['QueteController', 'index']);

$router->get('/api/user/profile', ['UserController', 'editProfile']);
$router->post('/api/user/update-profile', ['UserUpdateProfileController', 'index']);

$router->get('/api/maintenance', ['MaintenanceController', 'index']);
$router->get('/api/maintenance/parcours', ['MaintenanceController', 'ajaxParcours']);
$router->get('/api/maintenance/history', ['MaintenanceController', 'ajaxHistoryDiff']);
$router->post('/api/maintenance/update', ['MaintenanceController', 'update']);
$router->post('/api/maintenance/restore', ['MaintenanceController', 'restore']);
