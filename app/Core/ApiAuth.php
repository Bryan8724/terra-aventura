<?php

namespace Core;

use Core\Database;

class ApiAuth
{
    private const TOKEN_TTL = 604800; // 7 jours en secondes

    /*
    |--------------------------------------------------------------------------
    | Générer token
    |--------------------------------------------------------------------------
    */
    public static function generateToken(int $userId): string
    {
        $db = Database::getInstance();

        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + self::TOKEN_TTL
        );

        $stmt = $db->prepare("
            INSERT INTO api_tokens (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([$userId, $hash, $expiresAt]);

        return $token;
    }

    /*
    |--------------------------------------------------------------------------
    | Extraire token du header
    |--------------------------------------------------------------------------
    */
    private static function getBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$header && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header  = $headers['Authorization'] ?? null;
        }

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return trim(substr($header, 7));
    }

    /*
    |--------------------------------------------------------------------------
    | Récupérer utilisateur via token
    |--------------------------------------------------------------------------
    */
    public static function userFromToken(): ?array
    {
        $token = self::getBearerToken();

        if (!$token) {
            return null;
        }

        $hash = hash('sha256', $token);

        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT u.*
            FROM api_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.token_hash = ?
              AND t.expires_at > NOW()
              AND u.status = 'active'
            LIMIT 1
        ");

        $stmt->execute([$hash]);

        return $stmt->fetch() ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Exiger authentification
    |--------------------------------------------------------------------------
    */
    public static function requireAuth(): array
    {
        $user = self::userFromToken();

        if (!$user) {
            header('Content-Type: application/json');
            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Token invalide ou expiré'
            ]);

            exit;
        }

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Invalider token courant (logout API)
    |--------------------------------------------------------------------------
    */
    public static function invalidateCurrentToken(): void
    {
        $token = self::getBearerToken();

        if (!$token) {
            return;
        }

        $hash = hash('sha256', $token);

        $db = Database::getInstance();

        $stmt = $db->prepare("
            DELETE FROM api_tokens
            WHERE token_hash = ?
        ");

        $stmt->execute([$hash]);
    }

    /*
    |--------------------------------------------------------------------------
    | Nettoyage tokens expirés (optionnel)
    |--------------------------------------------------------------------------
    */
    public static function cleanupExpired(): void
    {
        $db = Database::getInstance();

        $db->exec("
            DELETE FROM api_tokens
            WHERE expires_at <= NOW()
        ");
    }
}
