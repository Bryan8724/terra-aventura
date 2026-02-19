<?php ob_start(); ?>

<?php
$isDev = getenv('APP_ENV') === 'dev';
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

if ($isDev && $isAdmin && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$lastDeploy = null;
if ($isDev && file_exists('/srv/scripts/last_deploy.txt')) {
    $lastDeploy = trim(file_get_contents('/srv/scripts/last_deploy.txt'));
}
?>

<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">

    <a href="/parcours" class="bg-white p-6 rounded shadow hover:shadow-lg transition block">
        <p class="text-gray-500">Parcours</p>
        <p class="text-3xl font-bold">🗺️</p>
        <p class="text-sm text-gray-400 mt-2">Gérer les parcours</p>
    </a>

    <a href="/parcours" class="bg-white p-6 rounded shadow hover:shadow-lg transition block">
        <p class="text-gray-500">POIZ</p>
        <p class="text-3xl font-bold">📍</p>
        <p class="text-sm text-gray-400 mt-2">Via les parcours</p>
    </a>

    <a href="/quetes"
       class="bg-white p-6 rounded shadow hover:shadow-lg transition block border-l-4
              <?= !empty($_SESSION['quetes_a_confirmer'] ?? []) ? 'border-orange-500' : 'border-blue-600' ?>">

        <p class="text-gray-500">Quêtes</p>
        <p class="text-3xl font-bold">🎯</p>
        <p class="text-sm text-gray-400 mt-2">Suivre l’avancement</p>
    </a>

    <div class="bg-white p-6 rounded shadow opacity-50 cursor-not-allowed">
        <p class="text-gray-500">Événements</p>
        <p class="text-3xl font-bold">🎉</p>
        <p class="text-sm text-gray-400 mt-2">Bientôt disponible</p>
    </div>

    <?php if ($isDev && $isAdmin): ?>

    <div class="bg-red-50 border-l-4 border-red-600 p-6 rounded shadow">

        <p class="text-red-600 font-semibold">Déploiement PROD</p>
        <p class="text-3xl font-bold">🚀</p>

        <?php if ($lastDeploy): ?>
            <p class="text-xs text-gray-500 mt-2">
                Dernier déploiement : <?= htmlspecialchars($lastDeploy) ?>
            </p>
        <?php endif; ?>

        <!-- Barre -->
        <div id="deploy-container" class="mt-4 hidden">
            <div class="w-full bg-gray-200 rounded overflow-hidden">
                <div id="deploy-progress"
                     class="h-5 transition-all duration-500"
                     style="width:0%; background-color:#3b82f6;">
                </div>
            </div>
            <p id="deploy-status-text" class="text-sm mt-2 text-gray-600"></p>
        </div>

        <!-- Spinner -->
        <div id="deploy-spinner" class="hidden mt-3">
            <div class="animate-spin rounded-full h-6 w-6 border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <form method="POST"
              action="/deploy"
              class="mt-4"
              onsubmit="return confirm('⚠️ Déployer vers la PROD ?');">

            <input type="hidden" name="csrf_token"
                   value="<?= $_SESSION['csrf_token'] ?>">

            <button type="submit"
                    id="deploy-button"
                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
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

<!-- Overlay -->
<div id="deploy-overlay"
     class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded shadow-lg text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent mx-auto mb-3"></div>
        <p class="font-semibold">Déploiement en cours...</p>
    </div>
</div>

<!-- Toast -->
<div id="deploy-toast"
     class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-3 rounded shadow-lg hidden">
     ✅ Déploiement terminé avec succès !
</div>

<?php if ($isDev && $isAdmin): ?>
<script>

document.addEventListener("DOMContentLoaded", function() {

    const statusUrl = "https://terra.bryanmargot19210.fr/deploy-status?token=MON_TOKEN_SUPER_SECRET_LONG_ET_COMPLEXE";

    const container = document.getElementById("deploy-container");
    const bar = document.getElementById("deploy-progress");
    const text = document.getElementById("deploy-status-text");
    const button = document.getElementById("deploy-button");
    const toast = document.getElementById("deploy-toast");
    const overlay = document.getElementById("deploy-overlay");
    const spinner = document.getElementById("deploy-spinner");

    let lastStatus = "";

    function colorByStatus(status) {
        switch(status) {
            case "pulling": return "#3b82f6";
            case "building": return "#f59e0b";
            case "verifying": return "#8b5cf6";
            case "finalizing": return "#6366f1";
            case "done": return "#10b981";
            default: return "#6b7280";
        }
    }

    function showToast() {
        toast.classList.remove("hidden");
        setTimeout(() => toast.classList.add("hidden"), 4000);
    }

    function checkDeployStatus() {

        fetch(statusUrl)
            .then(res => res.json())
            .then(data => {

                if (!data || typeof data.progress === "undefined") return;

                if (data.progress > 0 && data.progress < 100) {
                    container.classList.remove("hidden");
                    spinner.classList.remove("hidden");
                    overlay.classList.remove("hidden");
                    button.disabled = true;
                }

                bar.style.width = data.progress + "%";
                bar.style.backgroundColor = colorByStatus(data.status);
                text.innerText = "Statut : " + data.status + " (" + data.progress + "%)";

                if (data.status === "done" && lastStatus !== "done") {
                    spinner.classList.add("hidden");
                    overlay.classList.add("hidden");
                    button.disabled = false;
                    showToast();

                    setTimeout(() => {
                        container.classList.add("hidden");
                        bar.style.width = "0%";
                    }, 3000);
                }

                lastStatus = data.status;
            })
            .catch(err => {
                console.log("Erreur fetch status :", err);
            });
    }

    setInterval(checkDeployStatus, 2000);
    checkDeployStatus();

});

</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
$title = 'Dashboard';
require VIEW_PATH . '/partials/layout.php';
