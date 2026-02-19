<?php

namespace Controllers;

use Core\Auth;
use Core\Response;
use Core\ApiAuth;

class DashboardController
{
    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        /*
        |--------------------------------------------------------------------------
        | 🔐 VERSION API → TOKEN
        |--------------------------------------------------------------------------
        */
        if ($isApi) {

            $user = ApiAuth::requireAuth();

            Response::json([
                'success' => true,
                'message' => 'Dashboard chargé',
                'data'    => [
                    'user' => [
                        'id'       => $user['id'],
                        'username' => $user['username'],
                        'email'    => $user['email'],
                        'role'     => $user['role'],
                    ]
                ]
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🌐 VERSION WEB → SESSION
        |--------------------------------------------------------------------------
        */
        Auth::check();

        require VIEW_PATH . '/dashboard/index.php';
    }
}
