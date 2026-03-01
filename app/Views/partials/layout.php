<?php

use Core\Toast;
use Core\Auth;

$user = $_SESSION['user'] ?? null;
$username = $user['username'] ?? '';
$isAdmin  = ($user['role'] ?? '') === 'admin';

$env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod';
$isDev = $env !== 'prod';

$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

/*
|--------------------------------------------------------------------------
| Détection section active
|--------------------------------------------------------------------------
*/
$section = match (true) {
    $path === ''                               => 'dashboard',
    str_starts_with($path, 'zamela')           => 'zamela',
    str_starts_with($path, 'parcours')         => 'parcours',
    str_starts_with($path, 'maintenance')      => 'maintenance',
    str_starts_with($path, 'quetes'),
    str_starts_with($path, 'admin/quetes')     => 'quetes',
    str_starts_with($path, 'poiz')             => 'poiz',
    str_starts_with($path, 'evenement')        => 'evenement',
    str_starts_with($path, 'stats')            => 'stats',
    str_starts_with($path, 'admin/messages')   => 'messages',
    str_starts_with($path, 'admin/users')      => 'users',
    default                                    => '',
};

function navClass(string $name, string $current): string
{
    return $current === $name
        ? 'bg-blue-600 text-white font-semibold border-l-4 border-blue-300'
        : 'text-slate-300 hover:bg-slate-700 hover:text-white';
}

$quetesUrl = $isAdmin ? '/admin/quetes' : '/quetes';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Terra Aventura') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta name="robots" content="noindex, nofollow">

        <!-- ✅ Thème : appliqué AVANT rendu pour éviter le flash -->
    <script>
    (function(){
        // Thème : appliqué avant le rendu (évite le flash)
        // Lire le thème — 'light' par défaut si jamais défini
        var t = localStorage.getItem('ta_theme');
        if (t !== 'dark') {
            t = 'light';
            localStorage.setItem('ta_theme', 'light'); // Normalise
        }
        document.documentElement.setAttribute('data-theme', t);

        // Hauteur réelle iOS : window.innerHeight exclut la barre d'adresse
        // alors que 100vh l'inclut → contenu coupé en bas sur Safari mobile
        function fixHeight() {
            var h = window.innerHeight;
            var hp = h + 'px';
            document.documentElement.style.setProperty('--vh', (h * 0.01) + 'px');
            if (!document.body) return;
            // Body
            document.body.style.height = hp;
            document.body.style.maxHeight = hp;
            // Tous les enfants directs du body qui ont un style de hauteur
            var els = document.body.children;
            for (var i = 0; i < els.length; i++) {
                var s = els[i].getAttribute('style') || '';
                if (s.indexOf('display:flex') !== -1 || s.indexOf('display: flex') !== -1) {
                    els[i].style.height = hp;
                    els[i].style.maxHeight = hp;
                }
            }
            // Sidebar — a height:100vh en inline style, doit aussi être corrigé
            var sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.style.height = hp;
                sidebar.style.maxHeight = hp;
            }
        }
        fixHeight();
        window.addEventListener('load', fixHeight);
        window.addEventListener('resize', fixHeight);
        window.addEventListener('orientationchange', function() {
            setTimeout(fixHeight, 100);
            setTimeout(fixHeight, 400);
        });
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', fixHeight);
        }
    })();
    </script>
    <!-- Tailwind : dark mode désactivé (on gère via data-theme) -->
    <script>window.tailwind = {config: {darkMode: 'class'}};</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/toast.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <meta name="theme-color" content="#0f172a">
</head>

<body class="app-body bg-slate-100" style="height:100vh;overflow:hidden;margin:0">

<!-- TOASTS -->
<?php if (Toast::has()): ?>
<div class="toast-container">
    <?php foreach (Toast::get() as $toast): ?>
        <div class="toast <?= htmlspecialchars($toast['type']) ?>">
            <?= htmlspecialchars($toast['message']) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Overlay mobile -->
<div id="overlay"
     class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"
     onclick="toggleSidebar()"></div>

<div style="display:flex;height:100vh;overflow:hidden">

    <!-- SIDEBAR -->
    <aside id="sidebar"
           class="fixed md:static top-0 left-0 z-50
                  w-64
                  bg-gradient-to-b from-slate-900 to-slate-800
                  shadow-lg
                  transform -translate-x-full md:translate-x-0
                  transition-transform duration-300"
           style="height:100vh;flex-shrink:0">

        <div class="h-full flex flex-col">

            <!-- Logo -->
            <div class="p-5 border-b border-slate-700">
                <div class="text-xl font-bold text-white">Terra Aventura</div>
                <div class="text-xs text-slate-400">Interface de gestion</div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1 text-sm overflow-hidden">

                <a href="/"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('dashboard', $section) ?>">
                    📊 Dashboard
                </a>

                <a href="/poiz"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('poiz', $section) ?>">
                    📍 POIZ
                </a>

                <a href="/parcours"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('parcours', $section) ?>">
                    🗺️ Parcours
                </a>

                <a href="/zamela"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('zamela', $section) ?>">
                    ⚡ Zaméla
                </a>

                <a href="/evenement"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('evenement', $section) ?>">
                    🎉 Événements
                </a>

                <a href="/stats"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('stats', $section) ?>">
                    📊 Statistiques
                </a>

                <a href="/maintenance"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('maintenance', $section) ?>">
                    🛠 Maintenance
                </a>

                <a href="<?= $quetesUrl ?>"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('quetes', $section) ?>">
                    🎯 Quêtes
                </a>

                <?php if ($isAdmin): ?>
                <div class="mt-4 pt-4 border-t border-slate-700 text-xs text-slate-400 uppercase">
                    Administration
                </div>

                <a href="/admin/messages"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('messages', $section) ?>">
                    💬 Messagerie
                </a>

                <a href="/admin/users"
                   class="flex items-center gap-2 px-3 py-2 rounded <?= navClass('users', $section) ?>">
                    👥 Utilisateurs
                </a>
                <?php endif; ?>

            </nav>

            <!-- User -->
            <?php if ($user): ?>
            <div class="p-4 border-t border-slate-700 text-sm text-slate-300">
                <a href="/user/profile"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors duration-150
                          hover:bg-slate-700 hover:text-white text-slate-300">
                    👤 Modifier mon profil
                </a>
                <div class="mt-2 px-3 text-xs text-slate-400">
                    Connecté en tant que<br>
                    <strong><?= htmlspecialchars($username) ?></strong>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="p-3 text-xs text-slate-500 border-t border-slate-700">
                © <?= date('Y') ?> Terra Aventura
            </div>

        </div>
    </aside>

    <!-- MAIN -->
    <div class="flex flex-col min-w-0" style="flex:1;overflow:hidden;min-height:0">

        <!-- HEADER -->
        <header class="bg-white border-b shadow-sm sticky top-0 z-30 flex-shrink-0">
            <div class="h-14 px-4 flex items-center justify-between">

                <div class="flex items-center gap-3">
                    <button class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg
                                   hover:bg-slate-100 transition text-slate-600 text-xl"
                            onclick="toggleSidebar()">☰</button>

                    <?php if ($isDev): ?>
                        <span class="px-2.5 py-1 text-xs font-bold uppercase tracking-wider
                                     bg-red-600 text-white rounded-full shadow hidden sm:inline-flex">
                            ⚠ DEV
                        </span>
                    <?php endif; ?>

                    <!-- Titre page sur mobile (breadcrumb simplifié) -->
                    <span class="md:hidden text-sm font-semibold text-slate-700 truncate max-w-[140px]">
                        <?= htmlspecialchars($title ?? 'Terra Aventura') ?>
                    </span>
                </div>

                <?php if ($user): ?>
                <div class="flex gap-3 text-sm items-center">
                    <span class="hidden sm:inline text-slate-600"><?= htmlspecialchars($username) ?></span>
                    <a href="/logout"
                       class="text-sm font-semibold text-red-600 hover:text-red-800 transition px-2 py-1 rounded-lg hover:bg-red-50">
                        <span class="hidden sm:inline">Déconnexion</span>
                        <span class="sm:hidden">🚪</span>
                    </a>
                </div>
                <?php endif; ?>

            </div>
        </header>

        <!-- CONTENT -->
        <main class="overflow-y-auto p-3 sm:p-4 md:p-6" style="flex:1;min-height:0;padding-bottom:max(1.5rem,env(safe-area-inset-bottom))">
            <div class="max-w-7xl mx-auto">
                <?= $content ?? '' ?>
            </div>
        </main>

    </div>
</div>

<script src="/js/toast.js" defer></script>

<!-- ===== MODAL DE CONFIRMATION GLOBAL ===== -->
<div id="ta-confirm-overlay"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);
            align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1.25rem;box-shadow:0 20px 60px rgba(0,0,0,.25);
                padding:2rem;width:100%;max-width:420px;margin:1rem;animation:taSlideIn .18s ease">
        <div id="ta-confirm-icon" style="font-size:2.2rem;text-align:center;margin-bottom:.75rem"></div>
        <p id="ta-confirm-msg"
           style="font-size:1rem;font-weight:600;color:#1e293b;text-align:center;margin:0 0 .5rem"></p>
        <p id="ta-confirm-sub"
           style="font-size:.85rem;color:#64748b;text-align:center;margin:0 0 1.5rem"></p>
        <div style="display:flex;gap:.75rem;justify-content:center">
            <button id="ta-confirm-cancel"
                    style="padding:.6rem 1.4rem;border-radius:.75rem;border:1.5px solid #e2e8f0;
                           background:#f8fafc;color:#475569;font-weight:600;font-size:.875rem;cursor:pointer;">
                Annuler
            </button>
            <button id="ta-confirm-ok"
                    style="padding:.6rem 1.4rem;border-radius:.75rem;border:none;
                           background:#dc2626;color:#fff;font-weight:600;font-size:.875rem;cursor:pointer;">
                Confirmer
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL D'ALERTE GLOBAL ===== -->
<div id="ta-alert-overlay"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);
            align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:1.25rem;box-shadow:0 20px 60px rgba(0,0,0,.25);
                padding:2rem;width:100%;max-width:380px;margin:1rem;animation:taSlideIn .18s ease">
        <div id="ta-alert-icon" style="font-size:2.2rem;text-align:center;margin-bottom:.75rem"></div>
        <p id="ta-alert-msg"
           style="font-size:1rem;font-weight:600;color:#1e293b;text-align:center;margin:0 0 1.5rem"></p>
        <button id="ta-alert-ok"
                style="display:block;width:100%;padding:.65rem;border-radius:.75rem;border:none;
                       background:#3b82f6;color:#fff;font-weight:600;font-size:.875rem;cursor:pointer;">
            OK
        </button>
    </div>
</div>

<style>
@keyframes taSlideIn {
    from { opacity:0; transform:scale(.94) translateY(8px); }
    to   { opacity:1; transform:scale(1)   translateY(0); }
}
</style>

<script>
/* ---- Utilitaires modal Terra Aventura ---- */

/** Remplace window.confirm() — retourne une Promise<boolean> */
function taConfirm(msg, {sub = '', icon = '⚠️', okLabel = 'Confirmer', okColor = '#dc2626'} = {}) {
    return new Promise(resolve => {
        const overlay = document.getElementById('ta-confirm-overlay');
        document.getElementById('ta-confirm-icon').textContent = icon;
        document.getElementById('ta-confirm-msg').textContent  = msg;
        document.getElementById('ta-confirm-sub').textContent  = sub;
        const okBtn = document.getElementById('ta-confirm-ok');
        okBtn.textContent        = okLabel;
        okBtn.style.background   = okColor;
        overlay.style.display    = 'flex';

        function cleanup(result) {
            overlay.style.display = 'none';
            okBtn.removeEventListener('click', onOk);
            document.getElementById('ta-confirm-cancel').removeEventListener('click', onCancel);
            resolve(result);
        }
        function onOk()     { cleanup(true);  }
        function onCancel() { cleanup(false); }

        okBtn.addEventListener('click', onOk);
        document.getElementById('ta-confirm-cancel').addEventListener('click', onCancel);
    });
}

/** Remplace window.alert() — retourne une Promise */
function taAlert(msg, {icon = 'ℹ️', type = 'info'} = {}) {
    return new Promise(resolve => {
        const overlay = document.getElementById('ta-alert-overlay');
        const okBtn   = document.getElementById('ta-alert-ok');
        document.getElementById('ta-alert-icon').textContent = icon;
        document.getElementById('ta-alert-msg').textContent  = msg;

        const colors = { error:'#dc2626', success:'#16a34a', info:'#3b82f6', warning:'#d97706' };
        okBtn.style.background = colors[type] ?? colors.info;
        overlay.style.display  = 'flex';

        function onOk() {
            overlay.style.display = 'none';
            okBtn.removeEventListener('click', onOk);
            resolve();
        }
        okBtn.addEventListener('click', onOk);
    });
}

/* ---- Interception globale des formulaires avec data-confirm ---- */
document.addEventListener('submit', async function(e) {
    const form = e.target;
    const msg  = form.dataset.confirm;
    if (!msg) return; // pas de confirmation requise

    e.preventDefault();

    const icon     = form.dataset.confirmIcon     ?? '🗑️';
    const sub      = form.dataset.confirmSub      ?? '';
    const okLabel  = form.dataset.confirmOk       ?? 'Confirmer';
    const okColor  = form.dataset.confirmColor    ?? '#dc2626';

    const ok = await taConfirm(msg, { sub, icon, okLabel, okColor });
    if (ok) {
        // Désactiver l'interception pour ce submit et soumettre
        form.removeAttribute('data-confirm');
        form.submit();
    }
});
</script>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('hidden');
}
// Fermer sidebar au clic sur un lien (mobile)
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth < 768) {
        document.querySelectorAll('#sidebar a').forEach(function(a) {
            a.addEventListener('click', function() {
                document.getElementById('sidebar').classList.remove('open');
                document.getElementById('overlay').classList.add('hidden');
            });
        });
    }
});
</script>

</body>
</html>