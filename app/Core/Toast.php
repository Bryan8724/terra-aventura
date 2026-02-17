<?php

namespace Core;

class Toast
{
    /*
    |--------------------------------------------------------------------------
    | Types autorisés
    |--------------------------------------------------------------------------
    */
    private const ALLOWED_TYPES = ['success', 'error', 'info', 'warning'];

    /*
    |--------------------------------------------------------------------------
    | Ajouter un toast
    |--------------------------------------------------------------------------
    */
    public static function add(string $type, string $message): void
    {
        self::start();

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $type = 'info';
        }

        $_SESSION['toasts'][] = [
            'type'    => $type,
            'message' => $message
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Récupérer et vider
    |--------------------------------------------------------------------------
    */
    public static function get(): array
    {
        self::start();

        $toasts = $_SESSION['toasts'] ?? [];

        unset($_SESSION['toasts']);

        return $toasts;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérifier présence
    |--------------------------------------------------------------------------
    */
    public static function has(): bool
    {
        self::start();
        return !empty($_SESSION['toasts']);
    }

    /*
    |--------------------------------------------------------------------------
    | Vider explicitement
    |--------------------------------------------------------------------------
    */
    public static function clear(): void
    {
        self::start();
        unset($_SESSION['toasts']);
    }

    /*
    |--------------------------------------------------------------------------
    | Session start sécurisé
    |--------------------------------------------------------------------------
    */
    private static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
