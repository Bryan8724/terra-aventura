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
        if ($exception !== null) {
            $exClass = htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8');
            $exMsg   = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
            $exFile  = htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8');
            $exLine  = $exception->getLine();
            $exTrace = htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');

            if ($isDev) {
                // En dev : affichage direct sans mot de passe
                $debug = <<<HTML
                <div class="mt-10 text-left bg-slate-800 border border-slate-600 rounded-xl p-5 text-sm">
                    <h2 class="text-red-400 font-bold text-base mb-3">🐛 Détail technique (mode DEV)</h2>
                    <p class="text-red-300 font-mono mb-2">{$exClass} : {$exMsg}</p>
                    <p class="text-slate-500 text-xs mb-3">{$exFile} — ligne {$exLine}</p>
                    <pre class="text-slate-400 text-xs overflow-x-auto whitespace-pre-wrap">{$exTrace}</pre>
                </div>
                HTML;
            } else {
                // En prod : détail caché, protégé par mot de passe
                // Les données sont encodées en base64 pour éviter tout risque XSS direct
                $encodedClass = base64_encode($exClass);
                $encodedMsg   = base64_encode($exMsg);
                $encodedFile  = base64_encode($exFile . ' — ligne ' . $exLine);
                $encodedTrace = base64_encode($exTrace);

                $debug = <<<HTML
                <div class="mt-6" id="debug-section">
                    <!-- Bouton voir détail -->
                    <button
                        onclick="document.getElementById('debug-auth').classList.remove('hidden'); this.classList.add('hidden');"
                        class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-400 hover:text-white text-sm rounded-lg transition-colors border border-slate-600">
                        🔍 Voir le détail de l'erreur
                    </button>

                    <!-- Formulaire mot de passe -->
                    <div id="debug-auth" class="hidden mt-4">
                        <div class="bg-slate-800 border border-slate-600 rounded-xl p-5 text-sm max-w-sm mx-auto">
                            <p class="text-slate-400 mb-3 text-xs">🔐 Accès restreint — entrez le mot de passe développeur</p>
                            <div class="flex gap-2">
                                <input
                                    type="password"
                                    id="debug-password"
                                    placeholder="Mot de passe..."
                                    onkeydown="if(event.key==='Enter') checkDebugPassword();"
                                    class="flex-1 px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-white text-sm focus:outline-none focus:border-blue-500" />
                                <button
                                    onclick="checkDebugPassword()"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm transition-colors">
                                    OK
                                </button>
                            </div>
                            <p id="debug-error" class="hidden text-red-400 text-xs mt-2">❌ Mot de passe incorrect</p>
                        </div>
                    </div>

                    <!-- Détail technique (caché) -->
                    <div id="debug-detail" class="hidden mt-4 text-left bg-slate-800 border border-red-800 rounded-xl p-5 text-sm">
                        <h2 class="text-red-400 font-bold text-base mb-3">🐛 Détail technique (prod)</h2>
                        <p id="d-class" class="text-red-300 font-mono mb-2"></p>
                        <p id="d-file" class="text-slate-500 text-xs mb-3"></p>
                        <pre id="d-trace" class="text-slate-400 text-xs overflow-x-auto whitespace-pre-wrap"></pre>
                    </div>
                </div>

                <script>
                (function() {
                    var DATA = {
                        cls:   '{$encodedClass}',
                        msg:   '{$encodedMsg}',
                        file:  '{$encodedFile}',
                        trace: '{$encodedTrace}'
                    };

                    window.checkDebugPassword = function() {
                        var pwd = document.getElementById('debug-password').value;
                        // Hash SHA-256 côté client pour ne pas exposer le mot de passe en clair dans le JS
                        crypto.subtle.digest('SHA-256', new TextEncoder().encode(pwd)).then(function(buf) {
                            var hash = Array.from(new Uint8Array(buf)).map(function(b){ return b.toString(16).padStart(2,'0'); }).join('');
                            // SHA-256 de "Theo8724"
                            if (hash === 'd89a882bea3da5728e4aef91e2fe12927ad64524272eb4a802d266bdaa5425b4') {
                                showDebug();
                            } else {
                                document.getElementById('debug-error').classList.remove('hidden');
                                document.getElementById('debug-password').value = '';
                                document.getElementById('debug-password').focus();
                            }
                        });
                    };

                    function b64decode(str) {
                        try { return atob(str); } catch(e) { return ''; }
                    }

                    function showDebug() {
                        document.getElementById('debug-auth').classList.add('hidden');
                        var detail = document.getElementById('debug-detail');
                        detail.classList.remove('hidden');
                        document.getElementById('d-class').textContent = b64decode(DATA.cls) + ' : ' + b64decode(DATA.msg);
                        document.getElementById('d-file').textContent  = b64decode(DATA.file);
                        document.getElementById('d-trace').textContent = b64decode(DATA.trace);
                    }
                })();
                </script>
                HTML;
            }
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