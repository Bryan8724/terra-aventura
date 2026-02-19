<?php

namespace Controllers;

use Core\Auth;
use Core\ApiAuth;
use Core\Response;

class UserController
{
    public function editProfile(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        /*
        |--------------------------------------------------------------------------
        | 🔐 VERSION API
        |--------------------------------------------------------------------------
        */
        if ($isApi) {

            $user = ApiAuth::requireAuth();

            Response::json([
                'success' => true,
                'data' => [
                    'id'       => $user['id'],
                    'username' => $user['username'],
                    'email'    => $user['email'] ?? null,
                    'role'     => $user['role'] ?? null,
                    'status'   => $user['status'] ?? null,
                ]
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🌐 VERSION WEB
        |--------------------------------------------------------------------------
        */
        Auth::check();

        $title = 'Mon profil';

        ob_start();
        require VIEW_PATH . '/user/edit-profile.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }
}
