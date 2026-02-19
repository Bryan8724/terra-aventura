<?php

namespace Controllers;

use Core\Auth;
use Core\ApiAuth;
use Core\Response;
use Core\Database;

class UserUpdateProfileController
{
    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        /*
        |--------------------------------------------------------------------------
        | MÉTHODE
        |--------------------------------------------------------------------------
        */
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ], 405);
        }

        /*
        |--------------------------------------------------------------------------
        | AUTH
        |--------------------------------------------------------------------------
        */
        if ($isApi) {
            $user = ApiAuth::requireAuth();
        } else {
            Auth::check();
            \Csrf::check();
            $user = $_SESSION['user'];
        }

        $db = Database::getInstance();

        $email           = trim($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | VALIDATION EMAIL
        |--------------------------------------------------------------------------
        */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Adresse email invalide'
                ], 400);
            }

            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => 'Adresse email invalide'
            ];

            header('Location: /user/profile');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | MOT DE PASSE ACTUEL OBLIGATOIRE
        |--------------------------------------------------------------------------
        */
        if ($currentPassword === '') {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Mot de passe actuel requis'
                ], 400);
            }

            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => 'Mot de passe actuel requis'
            ];

            header('Location: /user/profile');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | VÉRIFICATION MOT DE PASSE
        |--------------------------------------------------------------------------
        */
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($currentPassword, $hash)) {

            if ($isApi) {
                Response::json([
                    'success' => false,
                    'message' => 'Mot de passe actuel incorrect'
                ], 401);
            }

            $_SESSION['toast'] = [
                'type' => 'error',
                'message' => 'Mot de passe actuel incorrect'
            ];

            header('Location: /user/profile');
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | MISE À JOUR EMAIL
        |--------------------------------------------------------------------------
        */
        $db->prepare(
            "UPDATE users SET email = ? WHERE id = ?"
        )->execute([$email, $user['id']]);

        /*
        |--------------------------------------------------------------------------
        | MISE À JOUR MOT DE PASSE (OPTIONNEL)
        |--------------------------------------------------------------------------
        */
        if (!empty($newPassword)) {

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $db->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            )->execute([$newHash, $user['id']]);
        }

        /*
        |--------------------------------------------------------------------------
        | VERSION API
        |--------------------------------------------------------------------------
        */
        if ($isApi) {

            Response::json([
                'success' => true,
                'message' => !empty($newPassword)
                    ? 'Profil et mot de passe mis à jour'
                    : 'Profil mis à jour',
                'data' => [
                    'email' => $email
                ]
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VERSION WEB
        |--------------------------------------------------------------------------
        */
        $_SESSION['user']['email'] = $email;

        $_SESSION['toast'] = [
            'type'    => 'success',
            'message' => !empty($newPassword)
                ? 'Profil et mot de passe mis à jour'
                : 'Profil mis à jour'
        ];

        header('Location: /user/profile');
        exit;
    }
}
