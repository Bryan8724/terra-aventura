<?php

namespace Core;

/**
 * ErrorPage — Affiche une page d'erreur HTML propre ou du JSON selon le contexte.
 *
 * Usage :
 *   ErrorPage::abort(404);                          // auto web/API
 *   ErrorPage::abort(403, 'Accès refusé');
 *   ErrorPage::render(500, 'Message', $exception);  // web uniquement
 *   ErrorPage::json(401, 'Non authentifié');        // API uniquement
 */
class ErrorPage
{
    private static array $defaults = [
        400 => ['Requête invalide',       'Les données envoyées sont incorrectes ou incomplètes.'],
        401 => ['Non authentifié',        'Vous devez être connecté pour accéder à cette page.'],
        403 => ['Accès refusé',           'Vous n\'avez pas les droits nécessaires pour accéder à cette ressource.'],
        404 => ['Page introuvable',       'La page que vous cherchez n\'existe pas ou a été déplacée.'],
        405 => ['Méthode non autorisée',  'Cette action n\'est pas autorisée sur cette URL.'],
        409 => ['Conflit de données',     'Une action concurrente a modifié ces données. Veuillez réessayer.'],
        500 => ['Erreur serveur',         'Une erreur inattendue s\'est produite. Veuillez réessayer ou contacter l\'administrateur.'],
    ];

    // ─────────────────────────────────────────────────────────────
    //  POINT D'ENTRÉE PRINCIPAL — détecte web vs API automatiquement
    // ─────────────────────────────────────────────────────────────
    public static function abort(
        int $code = 500,
        ?string $message = null,
        ?\Throwable $exception = null
    ): never {
        if (self::isApi()) {
            self::json($code, $message ?? self::$defaults[$code][1] ?? 'Erreur');
        }
        self::render($code, $message, $exception);
    }

    // ─────────────────────────────────────────────────────────────
    //  PAGE HTML PROPRE (contexte web)
    // ─────────────────────────────────────────────────────────────
    public static function render(
        int $code = 500,
        ?string $message = null,
        ?\Throwable $exception = null
    ): never {
        while (ob_get_level() > 0) ob_end_clean();

        http_response_code($code);

        $env   = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod';
        $isDev = ($env === 'dev');

        [$title, $defaultMsg] = self::$defaults[$code] ?? ['Erreur', 'Une erreur est survenue.'];
        $msg = htmlspecialchars($message ?? $defaultMsg, ENT_QUOTES, 'UTF-8');

        $emoji = match ($code) {
            400     => '⚠️',
            401     => '🔒',
            403     => '🚫',
            404     => '🔍',
            405     => '⛔',
            409     => '⚡',
            default => '💥',
        };

        $backLink  = isset($_SESSION['user']) ? '/' : '/login';
        $backLabel = isset($_SESSION['user']) ? 'Retour à l\'accueil' : 'Se connecter';

        $debug = '';
        if ($isDev && $exception !== null) {
            $exClass = htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8');
            $exMsg   = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
            $exFile  = htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8');
            $exLine  = $exception->getLine();
            $exTrace = htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');

            $debug = <<<HTML
            <div class="mt-10 text-left bg-slate-800 border border-slate-600 rounded-xl p-5 text-sm">
                <h2 class="text-red-400 font-bold text-base mb-3">🐛 Détail technique (mode DEV)</h2>
                <p class="text-red-300 font-mono mb-2">{$exClass} : {$exMsg}</p>
                <p class="text-slate-500 text-xs mb-3">{$exFile} — ligne {$exLine}</p>
                <pre class="text-slate-400 text-xs overflow-x-auto whitespace-pre-wrap">{$exTrace}</pre>
            </div>
            HTML;
        }

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$code} — {$title} · Terra Aventura</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-6">
            <div class="max-w-lg w-full text-center">

                <div class="text-8xl font-black text-slate-700 mb-2 select-none">{$code}</div>
                <div class="text-4xl mb-3">{$emoji}</div>
                <h1 class="text-2xl font-bold text-white mb-4">{$title}</h1>

                <p class="text-slate-400 mb-8 text-lg leading-relaxed">{$msg}</p>

                <div class="flex gap-3 justify-center flex-wrap">
                    <a href="{$backLink}"
                       class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium transition-colors">
                        {$backLabel}
                    </a>
                    <button onclick="history.back()"
                            class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors">
                        Retour
                    </button>
                </div>

                {$debug}

                <p class="mt-10 text-slate-600 text-sm">Terra Aventura</p>
            </div>
        </body>
        </html>
        HTML;

        exit;
    }

    // ─────────────────────────────────────────────────────────────
    //  RÉPONSE JSON STRUCTURÉE (contexte API)
    // ─────────────────────────────────────────────────────────────
    public static function json(int $code, string $message): never
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message, 'code' => $code]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    //  DÉTECTION CONTEXTE
    // ─────────────────────────────────────────────────────────────
    public static function isApi(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        return str_starts_with($path, '/api/');
    }
}