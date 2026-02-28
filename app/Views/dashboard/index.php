<?php ob_start(); ?>

<?php
$isDev   = getenv('APP_ENV') === 'dev';
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
$username = $_SESSION['user']['username'] ?? 'Explorateur';
$hasQuetes = !empty($_SESSION['quetes_a_confirmer'] ?? []);

if ($isDev && $isAdmin && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$lastDeploy = null;
if ($isDev && file_exists('/srv/scripts/last_deploy.txt')) {
    $lastDeploy = trim(file_get_contents('/srv/scripts/last_deploy.txt'));
}

// Progression
$progression = ($totalParcours > 0) ? round(($effectues / $totalParcours) * 100) : 0;
?>

<style>
.dash-card {
    background: #fff;
    border-radius: 1.25rem;
    border: 1px solid #f1f5f9;
    padding: 1.5rem;
    display: flex; flex-direction: column;
    gap: .75rem;
    transition: box-shadow .2s, transform .2s;
    text-decoration: none; color: inherit;
    position: relative; overflow: hidden;
}
.dash-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.dash-card .card-icon {
    width: 3rem; height: 3rem; border-radius: .85rem;
    display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
    flex-shrink: 0;
}
.dash-card .card-label { font-size: .78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.dash-card .card-value { font-size: 1.7rem; font-weight: 800; color: #1e293b; line-height: 1; }
.dash-card .card-sub { font-size: .78rem; color: #94a3b8; }
.dash-card.disabled { opacity: .5; cursor: not-allowed; }
.dash-card.disabled:hover { transform: none; box-shadow: none; }

.progress-track {
    height: .45rem; background: #f1f5f9; border-radius: 9999px; overflow: hidden; margin-top: .25rem;
}
.progress-fill {
    height: 100%; border-radius: 9999px;
    background: linear-gradient(90deg, #2563eb, #3b82f6);
    transition: width .6s ease;
}

.alert-dot {
    position: absolute; top: 1rem; right: 1rem;
    width: .6rem; height: .6rem; border-radius: 50%;
    background: #f59e0b; box-shadow: 0 0 0 3px #fef3c7;
}

.welcome-band {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    border-radius: 1.25rem; padding: 1.5rem 2rem;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.75rem; gap: 1rem;
}
</style>

<!-- Bandeau de bienvenue -->
<div class="welcome-band">
    <div>
        <p class="text-blue-200 text-sm font-medium mb-0.5">Bonjour,</p>
        <h1 class="text-white text-2xl font-bold"><?= htmlspecialchars($username) ?> 👋</h1>
        <p class="text-blue-200 text-sm mt-1">
            <?php if ($totalParcours > 0): ?>
                <?= $effectues ?> / <?= $totalParcours ?> parcours effectués
                <?php if ($effectues > 0): ?> · <?= $progression ?>% de progression<?php endif; ?>
            <?php else: ?>
                Bienvenue sur Terra Aventura
            <?php endif; ?>
        </p>
    </div>
    <?php if ($totalParcours > 0): ?>
    <div class="text-right flex-shrink-0">
        <div class="text-3xl font-black text-white"><?= $progression ?>%</div>
        <div class="text-blue-200 text-xs mt-0.5">progression</div>
        <div class="progress-track w-28 mt-2">
            <div class="progress-fill" style="width: <?= $progression ?>%; background: rgba(255,255,255,.8);"></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Grille principale : 6 cards sur 2 lignes si mobile, 3 cols sur desktop -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">

    <!-- Parcours -->
    <a href="/parcours" class="dash-card">
        <div class="card-icon bg-blue-50">🗺️</div>
        <div>
            <p class="card-label">Parcours</p>
            <p class="card-value"><?= $totalParcours ?></p>
            <p class="card-sub mt-1">
                <?php if ($effectues > 0): ?>
                    <span class="text-green-500 font-semibold"><?= $effectues ?> effectué<?= $effectues > 1 ? 's' : '' ?></span>
                <?php else: ?>
                    disponibles
                <?php endif; ?>
            </p>
            <?php if ($totalParcours > 0): ?>
                <div class="progress-track mt-2">
                    <div class="progress-fill" style="width: <?= $progression ?>%"></div>
                </div>
            <?php endif; ?>
        </div>
    </a>

    <!-- POIZ -->
    <a href="/poiz" class="dash-card">
        <div class="card-icon bg-violet-50">📍</div>
        <div>
            <p class="card-label">POIZ</p>
            <p class="card-value"><?= $totalPoiz ?></p>
            <p class="card-sub mt-1">personnages</p>
        </div>
    </a>

    <!-- Quêtes -->
    <a href="<?= $isAdmin ? '/admin/quetes' : '/quetes' ?>" class="dash-card">
        <?php if ($hasQuetes): ?>
            <span class="alert-dot"></span>
        <?php endif; ?>
        <div class="card-icon bg-amber-50">🎯</div>
        <div>
            <p class="card-label">Quêtes</p>
            <p class="card-value text-2xl">—</p>
            <p class="card-sub mt-1">
                <?= $hasQuetes ? '<span class="text-amber-500 font-semibold">À confirmer !</span>' : 'Suivre l\'avancement' ?>
            </p>
        </div>
    </a>

    <!-- Zaméla -->
    <a href="/zamela" class="dash-card">
        <div class="card-icon" style="background:#f5f3ff">⚡</div>
        <div>
            <p class="card-label">Zaméla</p>
            <p class="card-value"><?= $totalZamela ?></p>
            <p class="card-sub mt-1">
                <?php if ($zamelaEffectues > 0): ?>
                    <span style="color:#7c3aed;font-weight:600"><?= $zamelaEffectues ?> effectué<?= $zamelaEffectues > 1 ? 's' : '' ?></span>
                <?php else: ?>
                    éphémères
                <?php endif; ?>
            </p>
        </div>
    </a>

    <!-- Événements -->
    <a href="/evenement" class="dash-card">
        <div class="card-icon bg-orange-50">🎉</div>
        <div>
            <p class="card-label">Événements</p>
            <p class="card-value"><?= $totalEvenements ?></p>
            <p class="card-sub mt-1">
                <?php if ($evenementsEffectues > 0): ?>
                    <span class="text-orange-500 font-semibold"><?= $evenementsEffectues ?> participé<?= $evenementsEffectues > 1 ? 's' : '' ?></span>
                <?php else: ?>
                    à découvrir
                <?php endif; ?>
            </p>
        </div>
    </a>

    <!-- Statistiques -->
    <a href="/stats" class="dash-card">
        <div class="card-icon bg-emerald-50">📊</div>
        <div>
            <p class="card-label">Statistiques</p>
            <?php
            $totalItems = $totalParcours + $totalZamela + $totalEvenements;
            $doneItems  = $effectues + $zamelaEffectues + $evenementsEffectues;
            $scoreGlobal = $totalItems > 0 ? round($doneItems / $totalItems * 100) : 0;
            ?>
            <p class="card-value text-emerald-600"><?= $scoreGlobal ?>%</p>
            <p class="card-sub mt-1">score global</p>
            <?php if ($totalItems > 0): ?>
                <div class="progress-track mt-2">
                    <div class="progress-fill" style="width:<?= $scoreGlobal ?>%;background:linear-gradient(90deg,#059669,#10b981)"></div>
                </div>
            <?php endif; ?>
        </div>
    </a>

</div>

<?php if ($isDev && $isAdmin): ?>
<!-- Zone DEV admin -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- DEPLOY -->
    <div class="bg-red-50 border border-red-100 rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-xl">🚀</div>
            <div>
                <p class="font-semibold text-red-700">Déploiement PROD</p>
                <?php if ($lastDeploy): ?>
                    <p class="text-xs text-gray-400">Dernier : <?= htmlspecialchars($lastDeploy) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <input type="hidden" id="deploy-csrf" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="mb-3">
            <label class="block text-xs font-medium text-gray-500 mb-1">Message de commit <span class="text-gray-400 font-normal">(optionnel)</span></label>
            <input type="text" id="deploy-commit-msg" maxlength="100"
                   placeholder="ex: Ajout parcours Périgord"
                   class="w-full border border-red-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:border-red-400">
        </div>
        <button id="deploy-button" type="button"
                class="w-full bg-red-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">
            Lancer le déploiement
        </button>
        <a href="/deploy/log" class="block mt-2 text-center text-xs text-blue-600 hover:underline">📄 Voir le dernier log</a>
    </div>

    <!-- MODAL DEPLOY -->
    <div id="deploy-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,0.6);">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
            <p class="text-lg font-bold text-gray-800 mb-1">🚀 Déploiement en cours…</p>
            <p id="deploy-status-label" class="text-sm text-gray-500 mb-4">Initialisation…</p>
            <div class="w-full bg-gray-200 rounded-xl overflow-hidden mb-2">
                <div id="deploy-progress-bar" class="h-4 bg-red-600 transition-all duration-500" style="width:0%;"></div>
            </div>
            <p id="deploy-progress-text" class="text-sm text-gray-600 mb-6">0%</p>
            <button id="deploy-close" class="hidden w-full bg-green-600 text-white py-2.5 rounded-xl hover:bg-green-700 transition text-sm font-semibold">✅ Fermer</button>
            <p id="deploy-error" class="hidden text-sm text-red-600 mt-2"></p>
        </div>
    </div>

    <!-- BACKUP -->
    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-xl">💾</div>
            <div>
                <p class="font-semibold text-gray-700">Backup environnement DEV</p>
                <p class="text-xs text-gray-400">Télécharger une sauvegarde de la BDD</p>
            </div>
        </div>
        <div class="mb-3">
            <div class="w-full bg-gray-100 rounded-xl overflow-hidden">
                <div id="backup-progress" class="h-4 bg-blue-600 transition-all duration-300" style="width:0%;"></div>
            </div>
            <p id="backup-text" class="text-xs mt-1.5 text-gray-500">0%</p>
            <p id="backup-error" class="text-sm mt-2 text-red-600 hidden"></p>
        </div>
        <button id="backup-button" type="button"
                class="w-full bg-gray-800 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-900 transition">
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
        pushing:"⬆️ Push vers Git…",starting:"Initialisation…",pulling:"⬇️ Récupération Git (prod)…",
        building:"🔨 Build Docker…",backup:"💾 Sauvegarde BDD…",migrating:"🗄️ Migrations…",
        verifying:"🔍 Vérification…",finalizing:"✅ Finalisation…",done:"✅ Déploiement terminé !",
        error:"❌ Une erreur est survenue.",idle:"En attente…"
    };
    let pollInterval = null;
    function openModal() {
        progressBar.style.width="0%"; progressTxt.textContent="0%"; statusLabel.textContent="Initialisation…";
        closeBtn.classList.add("hidden"); errorMsg.classList.add("hidden"); errorMsg.textContent="";
        modal.classList.remove("hidden"); modal.classList.add("flex");
    }
    function closeModal() { modal.classList.add("hidden"); modal.classList.remove("flex"); if(pollInterval)clearInterval(pollInterval); }
    function updateProgress(status, progress) {
        progressBar.style.width=progress+"%"; progressTxt.textContent=progress+"%";
        statusLabel.textContent=STATUS_LABELS[status]??status;
        if(status==="done"){ progressBar.style.background="#16a34a"; closeBtn.classList.remove("hidden"); if(pollInterval)clearInterval(pollInterval); }
        if(status==="error"){ errorMsg.textContent="Consultez les logs pour plus de détails."; errorMsg.classList.remove("hidden"); closeBtn.classList.remove("hidden"); closeBtn.textContent="Fermer"; closeBtn.style.background="#dc2626"; if(pollInterval)clearInterval(pollInterval); }
    }
    function startPolling() {
        pollInterval=setInterval(async()=>{ try{ const res=await fetch("/deploy-status"); const data=await res.json(); updateProgress(data.status??"idle",data.progress??0); }catch(e){} },2000);
    }
    deployBtn.addEventListener("click", async function() {
        const ok = await taConfirm("Déployer vers la PROD ?", {
            sub: "Le code sera commité et poussé sur git avant le déploiement.",
            icon: "🚀", okLabel: "Déployer", okColor: "#dc2626"
        });
        if (!ok) return;
        openModal();
        try {
            const formData=new FormData(); formData.append("csrf_token",csrfToken); formData.append("commit_message",commitInput?.value?.trim()??"");
            const res=await fetch("/deploy",{method:"POST",body:formData}); const data=await res.json();
            if(!data.success) throw new Error(data.error||"Erreur lors du déploiement");
            startPolling();
        } catch(e) { statusLabel.textContent="❌ Échec"; errorMsg.textContent=e.message; errorMsg.classList.remove("hidden"); closeBtn.classList.remove("hidden"); }
    });
    closeBtn.addEventListener("click", function() { closeModal(); window.location.reload(); });
});
</script>

<script>
// ===================== BACKUP =====================
document.addEventListener("DOMContentLoaded", function() {
    const backupBtn=document.getElementById("backup-button");
    const backupBar=document.getElementById("backup-progress");
    const backupText=document.getElementById("backup-text");
    const backupError=document.getElementById("backup-error");
    if(!backupBtn) return;
    backupBtn.addEventListener("click", async function() {
        backupBtn.disabled=true; backupError.classList.add("hidden"); backupError.innerText="";
        try {
            const startRes=await fetch('/admin/dev-backup/start',{method:'POST'}); const startData=await startRes.json();
            if(!startData.success) throw new Error(startData.error||"Erreur start()");
            const interval=setInterval(async()=>{
                try {
                    const res=await fetch('/admin/dev-backup/progress'); const data=await res.json();
                    if(!data.success) throw new Error(data.error||"Erreur progress()");
                    const percent=data.progress||0; backupBar.style.width=percent+"%"; backupText.innerText=percent+"%";
                    if(percent>=100){ clearInterval(interval); window.location.href='/admin/dev-backup/download'; backupBtn.disabled=false; }
                } catch(err){ clearInterval(interval); backupError.innerText=err.message; backupError.classList.remove("hidden"); backupBtn.disabled=false; }
            },1000);
        } catch(err){ backupError.innerText=err.message; backupError.classList.remove("hidden"); backupBtn.disabled=false; }
    });
});
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
$title   = 'Dashboard';
require VIEW_PATH . '/partials/layout.php';
