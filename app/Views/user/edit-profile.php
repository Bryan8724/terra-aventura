<?php
// ✅ FIX 1 : plus de require_once manuel — l'autoloader gère le chargement
// ✅ FIX 2 : `use Core\Csrf` résout "Class Csrf not found"
//    (le require_once chargeait le fichier mais sans `use`, PHP cherchait `Csrf`
//     dans le namespace courant, d'où l'erreur au ligne 23)
use Core\Csrf;

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: /login');
    exit;
}
?>

<div class="max-w-2xl mx-auto w-full">

    <!-- En-tête -->
    <div class="mb-8 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-lg shadow-md">
            👤
        </div>
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Mon profil</h1>
            <p class="text-sm text-slate-400">Modifiez vos informations personnelles</p>
        </div>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">

        <!-- Bandeau utilisateur -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-5 flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg select-none">
                <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <p class="text-white font-semibold text-lg leading-tight">
                    <?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>
                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-xs font-medium
                    <?= ($user['role'] ?? '') === 'admin' ? 'bg-amber-400/20 text-amber-300' : 'bg-blue-400/20 text-blue-300' ?>">
                    <?= ($user['role'] ?? '') === 'admin' ? '⭐ Administrateur' : '👤 Utilisateur' ?>
                </span>
            </div>
        </div>

<!-- Apparence — carte indépendante AVANT le formulaire profil -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-4">
    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">🎨 Apparence</h2>
    <div class="grid grid-cols-2 gap-3" id="themeSelector">
        <button type="button" data-theme-val="light"
                class="theme-btn flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 transition text-sm font-semibold">
            ☀️ Clair
        </button>
        <button type="button" data-theme-val="dark"
                class="theme-btn flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 transition text-sm font-semibold">
            🌙 Sombre
        </button>
    </div>
    <p class="mt-2 text-xs text-slate-400">✅ Changement instantané — aucun mot de passe requis.</p>
</div>

        <!-- ✅ FIX 3 : remplacement de l'appel onsubmit="teraConfirm(...)" (fonction inexistante)
             par data-confirm, intercepté par le listener global déjà présent dans layout.php -->
        <form
            method="post"
            action="/user/update-profile"
            class="p-6 space-y-6"
            data-confirm="Confirmez-vous la modification de vos informations personnelles ?"
            data-confirm-icon="✏️"
            data-confirm-ok="Enregistrer"
            data-confirm-color="#2563eb"
        >
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

            <!-- Nom d'utilisateur (lecture seule) -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">
                    Nom d'utilisateur
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm">@</span>
                    <input
                        type="text"
                        value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        disabled
                        class="w-full pl-7 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm cursor-not-allowed"
                    >
                </div>
                <p class="mt-1 text-xs text-slate-400">Le nom d'utilisateur ne peut pas être modifié.</p>
            </div>

            <div class="border-t border-slate-100"></div>

            <!-- Email -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5" for="email">
                    Adresse e-mail
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="exemple@domaine.fr"
                >
            </div>

            <!-- Séparateur mot de passe -->
            <div class="relative flex items-center gap-3">
                <div class="flex-1 border-t border-slate-100"></div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 whitespace-nowrap">Changer le mot de passe</span>
                <div class="flex-1 border-t border-slate-100"></div>
            </div>

            <!-- Nouveau mot de passe -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5" for="new_password">
                    Nouveau mot de passe
                </label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="Laisser vide pour ne pas modifier"
                    autocomplete="new-password"
                >
            </div>

            <div class="border-t border-slate-100"></div>

            <!-- Mot de passe actuel -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5" for="current_password">
                    Mot de passe actuel <span class="text-red-400">*</span>
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    placeholder="Requis pour confirmer les changements"
                    autocomplete="current-password"
                >
                <p class="mt-1 text-xs text-slate-400">Votre mot de passe actuel est requis pour valider toute modification.</p>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                <a href="/" class="text-sm text-slate-500 hover:text-slate-700 transition text-center sm:text-left">
                    ← Retour au dashboard
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-xl shadow-sm
                           transition-all duration-150 active:scale-95"
                >
                    ✅ Enregistrer les modifications
                </button>
            </div>

        </form>
    </div>

    <?php if (($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod') !== 'prod'): ?>
    <p class="mt-3 text-xs text-slate-400 text-right">🔐 Token CSRF actif</p>
    <?php endif; ?>

</div>

<style>
.theme-btn {
    background: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
}
.theme-btn:hover { border-color: #3b82f6; color: #2563eb; background: #eff6ff; }
.theme-btn.active {
    border-color: #4f46e5 !important;
    background: #eef2ff !important;
    color: #4f46e5 !important;
}
[data-theme="dark"] .theme-btn { background: #263347; color: #94a3b8; border-color: rgba(255,255,255,.1); }
[data-theme="dark"] .theme-btn:hover { border-color: #6366f1; color: #a5b4fc; background: rgba(99,102,241,.1); }
[data-theme="dark"] .theme-btn.active { background: rgba(99,102,241,.25) !important; color: #818cf8 !important; border-color: #6366f1 !important; }
</style>
<script>
(function() {
    var current = localStorage.getItem('ta_theme') || 'light';

    function applyTheme(t) {
        // Normalize: si 'system' existait avant, basculer en 'light'
        if (t !== 'dark') t = 'light';
        document.documentElement.setAttribute('data-theme', t);
        localStorage.setItem('ta_theme', t);
        document.querySelectorAll('.theme-btn').forEach(function(btn) {
            btn.classList.toggle('active', btn.dataset.themeVal === t);
        });
    }

    // Init
    applyTheme(current);

    // Clicks
    document.querySelectorAll('.theme-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            applyTheme(btn.dataset.themeVal);
        });
    });
})();
</script>
