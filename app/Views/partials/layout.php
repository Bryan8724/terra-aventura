<?php

use Core\Toast;
use Core\Auth;

$user = $_SESSION['user'] ?? null;
$username = $user['username'] ?? '';
$isAdmin  = ($user['role'] ?? '') === 'admin';

$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

/*
|--------------------------------------------------------------------------
| Détection section active
|--------------------------------------------------------------------------
*/
$section = match (true) {
    $path === ''                               => 'dashboard',
    str_starts_with($path, 'parcours')         => 'parcours',
    str_starts_with($path, 'maintenance')      => 'maintenance',
    str_starts_with($path, 'quetes'),
    str_starts_with($path, 'admin/quetes')     => 'quetes',
    str_starts_with($path, 'poiz')             => 'poiz',
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="robots" content="noindex, nofollow">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/toast.css">
</head>

<body class="bg-gray-100 overflow-hidden">

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

<div class="flex h-[100dvh]">

    <!-- SIDEBAR -->
    <aside id="sidebar"
           class="fixed md:static top-0 left-0 z-50
                  h-[100dvh] w-64
                  bg-gradient-to-b from-slate-900 to-slate-800
                  shadow-lg
                  transform -translate-x-full md:translate-x-0
                  transition-transform duration-300">

        <div class="h-full flex flex-col">

            <!-- Logo -->
            <div class="p-5 border-b border-slate-700">
                <div class="text-xl font-bold text-white">Terra Aventura</div>
                <div class="text-xs text-slate-400">Interface de gestion</div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1 text-sm overflow-y-auto">

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
                <a href="/user/profile" class="hover:underline">
                    👤 Modifier mon profil
                </a>
                <div class="mt-2 text-xs text-slate-400">
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
    <div class="flex-1 flex flex-col min-w-0">

        <!-- HEADER -->
        <header class="bg-white border-b shadow-sm sticky top-0 z-30">
            <div class="h-14 px-4 flex items-center justify-between max-w-7xl mx-auto">

                <button class="md:hidden text-xl" onclick="toggleSidebar()">☰</button>

                <?php if ($user): ?>
                <div class="flex gap-4 text-sm">
                    <span><?= htmlspecialchars($username) ?></span>
                    <a href="/logout" class="text-red-600 hover:underline">
                        Déconnexion
                    </a>
                </div>
                <?php endif; ?>

            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <?= $content ?? '' ?>
            </div>
        </main>

    </div>
</div>

<script src="/js/toast.js" defer></script>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('overlay').classList.toggle('hidden');
}
</script>

</body>
</html>
