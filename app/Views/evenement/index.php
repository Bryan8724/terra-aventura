<?php
$user    = $_SESSION['user'] ?? null;
$isAdmin = ($user['role'] ?? '') === 'admin';
$activeSearch    = trim($_GET['search'] ?? '');
$activeDept      = trim($_GET['departement'] ?? '');
$activeEffectues = isset($_GET['effectues']);
$activeExpires   = isset($_GET['expires']);
$hasFilters = $activeSearch !== '' || $activeDept !== '' || $activeEffectues;
?>
<style>
.search-wrapper{position:relative}
.search-icon{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}
#evSearchInput:focus{border-color:#f97316;box-shadow:0 0 0 3px rgba(249,115,22,.15);outline:none}
.filter-pill{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border-radius:9999px;border:1.5px solid #e2e8f0;background:#fff;font-size:.8rem;font-weight:500;color:#475569;cursor:pointer;transition:all .18s;white-space:nowrap;user-select:none}
.filter-pill:hover{border-color:#fed7aa;color:#c2410c;background:#fff7ed}
.filter-pill.active{background:#ea580c;border-color:#ea580c;color:#fff}
.filter-select{padding:.45rem 2.2rem .45rem .9rem;border-radius:9999px;border:1.5px solid #e2e8f0;background:#fff;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:calc(100% - .65rem) center;appearance:none;font-size:.8rem;font-weight:500;color:#475569;cursor:pointer}
.filter-select:focus{outline:none;border-color:#f97316}
.filter-select.has-value{background-color:#fff7ed;border-color:#fed7aa;color:#c2410c}
.btn-reset{display:inline-flex;align-items:center;gap:.4rem;padding:.45rem .9rem;border-radius:9999px;border:1.5px solid #fca5a5;background:#fff;color:#dc2626;font-size:.8rem;font-weight:500;text-decoration:none;cursor:pointer}
#evFilterPanel{overflow:hidden;transition:max-height .3s ease,opacity .3s ease;max-height:0;opacity:0}
#evFilterPanel.open{max-height:200px;opacity:1}
.tab-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border-radius:.65rem;font-size:.85rem;font-weight:600;border:none;cursor:pointer;transition:all .18s;text-decoration:none}
.tab-btn.active{background:#ea580c;color:#fff;box-shadow:0 2px 8px rgba(234,88,12,.25)}
.tab-btn.inactive{background:#f1f5f9;color:#475569}
.tab-btn.inactive:hover{background:#e2e8f0}
.tab-btn.expired-tab.inactive{background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0}
.tab-btn.expired-tab.active{background:#64748b;color:#fff;box-shadow:0 2px 8px rgba(100,116,139,.25)}
</style>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Événements 🎉</h1>
        <p class="text-sm text-gray-500">Événements Terra Aventura avec leurs parcours exclusifs</p>
    </div>
    <div class="flex items-center gap-2">
        <!-- Onglet Événements expirés -->
        <a href="<?= $activeExpires ? '/evenement' : '/evenement?expires=1' ?>"
           class="tab-btn expired-tab <?= $activeExpires ? 'active' : 'inactive' ?>">
            ⛔ <?= $activeExpires ? 'Expirés' : 'Expirés' ?>
        </a>
        <?php if ($isAdmin): ?>
            <a href="/evenement/create"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 text-white rounded-xl text-sm font-medium hover:bg-orange-600 shadow-sm transition">
                ➕ Ajouter
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($activeExpires): ?>
<!-- BANDEAU INFO EXPIRÉS -->
<div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 mb-5 flex items-center gap-3">
    <span class="text-2xl">⛔</span>
    <div>
        <p class="text-sm font-semibold text-slate-700">Événements expirés</p>
        <p class="text-xs text-slate-500">Événements dont la date de fin est passée. Vous pouvez toujours valider votre participation rétroactivement.</p>
    </div>
</div>
<?php endif; ?>

<!-- BARRE FILTRES -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
    <div class="p-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
        <div class="search-wrapper flex-1">
            <span class="search-icon">🔍</span>
            <input type="text" id="evSearchInput"
                   value="<?= htmlspecialchars($activeSearch) ?>"
                   placeholder="Rechercher un événement…"
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm" style="padding-left:2.75rem">
        </div>
        <button id="evToggleFilters" type="button"
                class="filter-pill shrink-0 <?= $activeDept ? 'active' : '' ?>">⚙️ Département</button>
        <?php if (!$activeExpires): ?>
        <button id="evToggleEffectues" type="button"
                class="filter-pill shrink-0 <?= $activeEffectues ? 'active' : '' ?>">✔ Effectués</button>
        <?php endif; ?>
        <?php if ($hasFilters): ?>
            <a href="/evenement<?= $activeExpires ? '?expires=1' : '' ?>" class="btn-reset shrink-0">✕ Reset</a>
        <?php endif; ?>
    </div>
    <div id="evFilterPanel" class="<?= $activeDept ? 'open' : '' ?>">
        <div class="px-4 pb-4 pt-3 border-t border-gray-100">
            <select id="evDeptSelect" class="filter-select <?= $activeDept ? 'has-value' : '' ?>">
                <option value="">Tous les départements</option>
                <?php foreach ($departements as $code => $nom): ?>
                    <option value="<?= $code ?>" <?= $activeDept === $code ? 'selected' : '' ?>>
                        <?= $code ?> — <?= htmlspecialchars($nom) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div id="evContainer">
    <?php require __DIR__ . '/_list.php'; ?>
</div>

<!-- MODAL VALIDATION -->
<div id="evValidateModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold text-gray-800">🎉 Valider cet événement</h2>
            <button onclick="closeEvModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 text-2xl">×</button>
        </div>
        <form method="post" action="/evenement/valider" class="space-y-4">
            <input type="hidden" name="evenement_id" id="evModalId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de participation</label>
                <input type="date" name="date" required
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeEvModal()"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm hover:bg-gray-50">Annuler</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-orange-500 text-white rounded-xl text-sm font-medium hover:bg-orange-600 shadow-sm">
                    🎉 Confirmer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const evState = {
    search: <?= json_encode($activeSearch) ?>,
    departement: <?= json_encode($activeDept) ?>,
    effectues: <?= json_encode($activeEffectues) ?>,
    expires: <?= json_encode($activeExpires) ?>,
};
const evContainer = document.getElementById('evContainer');
let evDebounce;

function evBuildParams() {
    const p = new URLSearchParams();
    if (evState.search)      p.set('search', evState.search);
    if (evState.departement) p.set('departement', evState.departement);
    if (evState.effectues)   p.set('effectues', '1');
    if (evState.expires)     p.set('expires', '1');
    p.set('ajax', '1');
    return p.toString();
}

function loadEvenements(params) {
    evContainer.style.opacity = '.5'; evContainer.style.pointerEvents = 'none';
    fetch('/evenement?' + (params ?? evBuildParams()))
        .then(r => r.text()).then(html => {
            evContainer.innerHTML = html;
            evContainer.style.opacity = '1'; evContainer.style.pointerEvents = '';
        });
}

document.getElementById('evSearchInput').addEventListener('input', function() {
    evState.search = this.value.trim(); clearTimeout(evDebounce); evDebounce = setTimeout(loadEvenements, 380);
});
document.getElementById('evToggleFilters').addEventListener('click', () => {
    document.getElementById('evFilterPanel').classList.toggle('open');
});
document.getElementById('evDeptSelect').addEventListener('change', function() {
    evState.departement = this.value; this.classList.toggle('has-value', !!this.value); loadEvenements();
});
<?php if (!$activeExpires): ?>
document.getElementById('evToggleEffectues').addEventListener('click', function() {
    evState.effectues = !evState.effectues; this.classList.toggle('active', evState.effectues); loadEvenements();
});
<?php endif; ?>

function openEvModal(id) {
    document.getElementById('evModalId').value = id;
    const m = document.getElementById('evValidateModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function closeEvModal() {
    const m = document.getElementById('evValidateModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
document.getElementById('evValidateModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeEvModal(); });
</script>
