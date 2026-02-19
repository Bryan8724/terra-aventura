<?php ob_start(); ?>

<?php
$isDev = getenv('APP_ENV') === 'dev';
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

// CSRF
if ($isDev && $isAdmin && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Dernier déploiement
$lastDeploy = null;
if ($isDev && file_exists('/srv/scripts/last_deploy.txt')) {
    $lastDeploy = trim(file_get_contents('/srv/scripts/last_deploy.txt'));
}

// Déploiement en cours ?
$isDeployRunning = $isDev && file_exists('/tmp/deploy.lock');
?>

<?php if ($isDeployRunning): ?>
<script>
    // Refresh automatique toutes les 5 secondes pendant le déploiement
    setTimeout(() => location.reload(), 5000);
</script>
<?php endif; ?>

<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">

    <!-- Parcours -->
    <a href="/parcours"
       class="bg-white p-6 rounded shadow hover:shadow-lg transition block">
        <p class="text-gray-500">Parcours</p>
        <p class="text-3xl font-bold">🗺️</p>
        <p class="text-sm text-gray-400 mt-2">
            Gérer les parcours
        </p>
    </a>

    <!-- POIZ -->
    <a href="/parcours"
       class="bg-white p-6 rounded shadow hover:shadow-lg transition block">
        <p class="text-gray-500">POIZ</p>
        <p class="text-3xl font-bold">📍</p>
        <p class="text-sm text-gray-400 mt-2">
            Via les parcours
        </p>
    </a>

    <!-- Quêtes -->
    <a href="/quetes"
       class="bg-white p-6 rounded shadow hover:shadow-lg transition block border-l-4
              <?= !empty($_SESSION['quetes_a_confirmer'] ?? []) ? 'border-orange-500' : 'border-blue-600' ?>">

        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-500">Quêtes</p>
                <p class="text-3xl font-bold">🎯</p>
            </div>

            <?php if (!empty($_SESSION['quetes_a_confirmer'] ?? [])): ?>
                <span class="text-xs bg-orange-500 text-white px-2 py-1 rounded-full animate-pulse">
                    Action requise
                </span>
            <?php endif; ?>
        </div>

        <p class="text-sm text-gray-400 mt-2">
            Suivre l’avancement des quêtes
        </p>
    </a>

    <!-- Événements -->
    <div class="bg-white p-6 rounded shadow opacity-50 cursor-not-allowed">
        <p class="text-gray-500">Événements</p>
        <p class="text-3xl font-bold">🎉</p>
        <p class="text-sm text-gray-400 mt-2">
            Bientôt disponible
        </p>
    </div>

    <!-- 🚀 Déploiement PROD -->
    <?php if ($isDev && $isAdmin): ?>

        <div class="bg-red-50 border-l-4 border-red-600 p-6 rounded shadow hover:shadow-lg transition block">

            <p class="text-red-600 font-semibold">Déploiement PROD</p>
            <p class="text-3xl font-bold">🚀</p>
            <p class="text-sm text-gray-600 mt-2">
                Mettre à jour la production
            </p>

            <?php if ($lastDeploy): ?>
                <p class="text-xs text-gray-500 mt-2">
                    Dernier déploiement : <?= htmlspecialchars($lastDeploy) ?>
                </p>
            <?php endif; ?>

            <?php if ($isDeployRunning): ?>
                <div class="mt-3 text-sm text-yellow-600 font-semibold animate-pulse">
                    🟡 Déploiement en cours...
                </div>
            <?php endif; ?>

            <form method="POST" action="/deploy"
                  onsubmit="return confirm('⚠️ Déployer la version DEV vers la PROD ?');"
                  class="mt-4">

                <input type="hidden" name="csrf_token"
                       value="<?= $_SESSION['csrf_token'] ?>">

                <button type="submit"
                        <?= $isDeployRunning ? 'disabled' : '' ?>
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700
                               <?= $isDeployRunning ? 'opacity-50 cursor-not-allowed' : '' ?>">
                    Lancer le déploiement
                </button>
            </form>

            <a href="/deploy/log"
               class="mt-3 inline-block text-sm text-blue-600 hover:underline">
               📄 Voir le dernier log
            </a>

        </div>

    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
$title = 'Dashboard';
require VIEW_PATH . '/partials/layout.php';
