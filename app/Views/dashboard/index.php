<?php ob_start(); ?>

<?php
$isDev   = getenv('APP_ENV') === 'dev';
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

    <a href="/poiz" class="bg-white p-6 rounded shadow hover:shadow-lg transition block">
        <p class="text-gray-500">POIZ</p>
        <p class="text-3xl font-bold">📍</p>
        <p class="text-sm text-gray-400 mt-2">Gestion des points</p>
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

</div>

<?php if ($isDev && $isAdmin): ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">

    <!-- ================= DEPLOY ================= -->

    <div class="bg-red-50 border-l-4 border-red-600 p-6 rounded shadow">

        <p class="text-red-600 font-semibold">Déploiement PROD</p>
        <p class="text-3xl font-bold">🚀</p>

        <?php if ($lastDeploy): ?>
            <p class="text-xs text-gray-500 mt-2">
                Dernier déploiement : <?= htmlspecialchars($lastDeploy) ?>
            </p>
        <?php endif; ?>

        <input type="hidden" id="deploy-csrf" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="mt-4">
            <label class="block text-sm text-gray-600 mb-1">Message de commit <span class="text-gray-400">(optionnel)</span></label>
            <input type="text"
                   id="deploy-commit-msg"
                   maxlength="100"
                   placeholder="ex: Ajout parcours Périgord"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-red-400">
        </div>

        <button id="deploy-button"
                type="button"
                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
            Lancer le déploiement
        </button>

        <a href="/deploy/log"
           class="mt-3 inline-block text-sm text-blue-600 hover:underline">
           📄 Voir le dernier log
        </a>
    </div>

    <!-- ================= MODAL DÉPLOIEMENT ================= -->

    <div id="deploy-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center"
         style="background:rgba(0,0,0,0.6);">

        <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mx-4">

            <p class="text-lg font-bold text-gray-800 mb-1">🚀 Déploiement en cours…</p>
            <p id="deploy-status-label" class="text-sm text-gray-500 mb-4">Initialisation…</p>

            <!-- Barre de progression -->
            <div class="w-full bg-gray-200 rounded overflow-hidden mb-2">
                <div id="deploy-progress-bar"
                     class="h-5 bg-red-600 transition-all duration-500"
                     style="width:0%;">
                </div>
            </div>
            <p id="deploy-progress-text" class="text-sm text-gray-600 mb-6">0%</p>

            <!-- Bouton fermer (visible uniquement quand terminé) -->
            <button id="deploy-close"
                    class="hidden w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
                ✅ Fermer
            </button>

            <!-- Message erreur -->
            <p id="deploy-error" class="hidden text-sm text-red-600 mt-2"></p>
        </div>
    </div>

    <!-- ================= BACKUP ================= -->

    <div class="bg-white border-l-4 border-red-600 p-6 rounded shadow">

        <p class="text-red-600 font-semibold">Backup environnement DEV</p>
        <p class="text-3xl font-bold">💾</p>

        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded overflow-hidden">
                <div id="backup-progress"
                     class="h-5 bg-red-600 transition-all duration-300"
                     style="width:0%;">
                </div>
            </div>
            <p id="backup-text" class="text-sm mt-2 text-gray-600">0%</p>
            <p id="backup-error" class="text-sm mt-2 text-red-600 hidden"></p>
        </div>

        <button id="backup-button"
                type="button"
                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
            Lancer le backup
        </button>
    </div>

</div>

<script>
// ===================== DEPLOY =====================
document.addEventListener("DOMContentLoaded", function () {

    const deployBtn   = document.getElementById("deploy-button");
    const modal       = document.getElementById("deploy-modal");
    const progressBar = document.getElementById("deploy-progress-bar");
    const progressTxt = document.getElementById("deploy-progress-text");
    const statusLabel = document.getElementById("deploy-status-label");
    const closeBtn    = document.getElementById("deploy-close");
    const errorMsg    = document.getElementById("deploy-error");
    const csrfToken   = document.getElementById("deploy-csrf")?.value;
    const commitInput = document.getElementById("deploy-commit-msg");

    if (!deployBtn) return;

    const STATUS_LABELS = {
        pushing:    "⬆️ Push du code vers Git…",
        starting:   "Initialisation…",
        pulling:    "⬇️ Récupération du code Git (prod)…",
        building:   "🔨 Build des containers Docker…",
        backup:     "💾 Sauvegarde de la base de données…",
        migrating:  "🗄️ Exécution des migrations…",
        verifying:  "🔍 Vérification des containers…",
        finalizing: "✅ Finalisation…",
        done:       "✅ Déploiement terminé avec succès !",
        error:      "❌ Une erreur est survenue.",
        idle:       "En attente…"
    };

    let pollInterval = null;

    function openModal() {
        progressBar.style.width = "0%";
        progressTxt.textContent = "0%";
        statusLabel.textContent = "Initialisation…";
        closeBtn.classList.add("hidden");
        errorMsg.classList.add("hidden");
        errorMsg.textContent = "";
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }

    function closeModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        if (pollInterval) clearInterval(pollInterval);
    }

    function updateProgress(status, progress) {
        progressBar.style.width = progress + "%";
        progressTxt.textContent = progress + "%";
        statusLabel.textContent = STATUS_LABELS[status] ?? status;

        if (status === "done") {
            progressBar.classList.replace("bg-red-600", "bg-green-600");
            closeBtn.classList.remove("hidden");
            if (pollInterval) clearInterval(pollInterval);
        }

        if (status === "error") {
            errorMsg.textContent = "Consultez les logs pour plus de détails.";
            errorMsg.classList.remove("hidden");
            closeBtn.classList.remove("hidden");
            closeBtn.textContent = "Fermer";
            closeBtn.classList.replace("bg-green-600", "bg-red-600");
            closeBtn.classList.replace("hover:bg-green-700", "hover:bg-red-700");
            if (pollInterval) clearInterval(pollInterval);
        }
    }

    function startPolling() {
        pollInterval = setInterval(async () => {
            try {
                const res  = await fetch("/deploy-status");
                const data = await res.json();
                updateProgress(data.status ?? "idle", data.progress ?? 0);
            } catch (e) {
                // Silencieux — on continue de poller
            }
        }, 2000);
    }

    deployBtn.addEventListener("click", async function () {

        if (!confirm("⚠️ Déployer vers la PROD ?\n\nLe code sera commité et poussé sur git avant le déploiement.")) return;

        openModal();

        try {
            const formData = new FormData();
            formData.append("csrf_token", csrfToken);
            formData.append("commit_message", commitInput?.value?.trim() ?? "");

            const res  = await fetch("/deploy", { method: "POST", body: formData });
            const data = await res.json();

            if (!data.success) {
                throw new Error(data.error || "Erreur lors du déploiement");
            }

            // Git push OK → polling du statut prod
            startPolling();

        } catch (e) {
            statusLabel.textContent = "❌ Échec";
            errorMsg.textContent    = e.message;
            errorMsg.classList.remove("hidden");
            closeBtn.classList.remove("hidden");
        }
    });

    closeBtn.addEventListener("click", function () {
        closeModal();
        // Recharge la page pour afficher le nouveau "Dernier déploiement"
        window.location.reload();
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const backupBtn   = document.getElementById("backup-button");
    const backupBar   = document.getElementById("backup-progress");
    const backupText  = document.getElementById("backup-text");
    const backupError = document.getElementById("backup-error");

    if (!backupBtn) return;

    backupBtn.addEventListener("click", async function() {

        backupBtn.disabled = true;
        backupError.classList.add("hidden");
        backupError.innerText = "";

        try {

            // Lancer backup
            const startRes = await fetch('/admin/dev-backup/start', { method: 'POST' });
            const startData = await startRes.json();

            if (!startData.success) {
                throw new Error(startData.error || "Erreur start()");
            }

            // Polling progression
            const interval = setInterval(async () => {

                try {
                    const res = await fetch('/admin/dev-backup/progress');
                    const data = await res.json();

                    if (!data.success) {
                        throw new Error(data.error || "Erreur progress()");
                    }

                    const percent = data.progress || 0;

                    backupBar.style.width = percent + "%";
                    backupText.innerText = percent + "%";

                    if (percent >= 100) {
                        clearInterval(interval);
                        window.location.href = '/admin/dev-backup/download';
                        backupBtn.disabled = false;
                    }

                } catch (err) {
                    clearInterval(interval);
                    backupError.innerText = err.message;
                    backupError.classList.remove("hidden");
                    backupBtn.disabled = false;
                }

            }, 1000);

        } catch (err) {
            backupError.innerText = err.message;
            backupError.classList.remove("hidden");
            backupBtn.disabled = false;
        }

    });

});
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
$title   = 'Dashboard';
require VIEW_PATH . '/partials/layout.php';