<?php
$user    = $_SESSION['user'] ?? null;
$isAdmin = isset($user['role']) && $user['role'] === 'admin';

$activeSearch    = trim($_GET['search'] ?? '');
$activeDept      = $_GET['departement'] ?? [];
$activeEffectues = isset($_GET['effectues']);
$hasFilters      = $activeSearch !== '' || !empty($activeDept) || $activeEffectues;
?>

<style>
.search-wrapper { position: relative; }
.search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
#zSearchInput { padding-left: 2.75rem; }
#zSearchInput:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.15); outline: none; }
.filter-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .9rem; border-radius: 9999px; border: 1.5px solid #e2e8f0; background: #fff; font-size: .8rem; font-weight: 500; color: #475569; cursor: pointer; transition: all .18s; white-space: nowrap; user-select: none; }
.filter-pill:hover { border-color: #c4b5fd; color: #6d28d9; background: #f5f3ff; }
.filter-pill.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }
.filter-select { padding: .45rem 2.2rem .45rem .9rem; border-radius: 9999px; border: 1.5px solid #e2e8f0; background: #fff; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: calc(100% - .65rem) center; appearance: none; font-size: .8rem; font-weight: 500; color: #475569; cursor: pointer; }
.filter-select:focus { outline: none; border-color: #7c3aed; }
.filter-select.has-value { background-color: #f5f3ff; border-color: #c4b5fd; color: #6d28d9; }
.btn-reset { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .9rem; border-radius: 9999px; border: 1.5px solid #fca5a5; background: #fff; color: #dc2626; font-size: .8rem; font-weight: 500; text-decoration: none; cursor: pointer; }
.btn-reset:hover { background: #fef2f2; }
#zFilterPanel { overflow: hidden; transition: max-height .3s ease, opacity .3s ease; max-height: 0; opacity: 0; }
#zFilterPanel.open { max-height: 200px; opacity: 1; }
.zamela-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .65rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; background: linear-gradient(135deg, #7c3aed, #a78bfa); color: #fff; }
</style>

<!-- HEADER -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">
            Zaméla <span class="zamela-badge">⚡ Éphémères</span>
        </h1>
        <p class="text-sm text-gray-500">Parcours événementiels disponibles sur une période limitée</p>
    </div>
    <?php if ($isAdmin): ?>
        <a href="/zamela/create"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 text-white rounded-xl text-sm font-medium hover:bg-violet-700 shadow-sm transition">
            ➕ Ajouter
        </a>
    <?php endif; ?>
</div>

<!-- BARRE RECHERCHE + FILTRES -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
    <div class="p-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

        <div class="search-wrapper flex-1">
            <span class="search-icon">🔍</span>
            <input type="text" id="zSearchInput"
                   value="<?= htmlspecialchars($activeSearch) ?>"
                   placeholder="Rechercher un Zaméla, une ville…"
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400">
        </div>

        <button id="zToggleFilters" type="button"
                class="filter-pill shrink-0 <?= !empty($activeDept) ? 'active' : '' ?>">
            ⚙️ Département
            <?php if (!empty($activeDept)): ?>
                <span class="ml-1 w-5 h-5 flex items-center justify-center rounded-full bg-white/25 text-xs font-bold"><?= count((array)$activeDept) ?></span>
            <?php endif; ?>
        </button>

        <button id="zToggleEffectues" type="button"
                class="filter-pill shrink-0 <?= $activeEffectues ? 'active' : '' ?>">
            ✔ Effectués
        </button>

        <?php if ($hasFilters): ?>
            <a href="/zamela" class="btn-reset shrink-0">✕ Réinitialiser</a>
        <?php endif; ?>
    </div>

    <div id="zFilterPanel" class="<?= !empty($activeDept) ? 'open' : '' ?>">
        <div class="px-4 pb-4 pt-3 border-t border-gray-100">
            <div class="flex flex-col gap-1.5 max-w-xs">
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide pl-1">Département</label>
                <select id="zDeptSelect" class="filter-select w-full <?= !empty($activeDept) ? 'has-value' : '' ?>">
                    <option value="">Tous les départements</option>
                    <?php foreach ($departements as $code => $nom): ?>
                        <option value="<?= $code ?>"
                            <?= (is_array($activeDept) && in_array((string)$code, array_map('strval', $activeDept))) ? 'selected' : '' ?>>
                            <?= $code ?> — <?= htmlspecialchars($nom) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- LISTE -->
<div id="zamelaContainer">
    <?php require __DIR__ . '/_list.php'; ?>
</div>

<!-- MODAL VALIDATION -->
<div id="zValidateModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold text-gray-800">⚡ Valider ce Zaméla</h2>
            <button type="button" onclick="closeZValidateModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 text-2xl leading-none">×</button>
        </div>
        <form method="post" action="/parcours/valider" class="space-y-4">
            <input type="hidden" name="parcours_id" id="zModalId">
            <input type="hidden" name="redirect" value="/zamela">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de réalisation</label>
                <input type="date" name="date" required
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-100 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Heure <span class="text-gray-400 font-normal">(optionnel)</span></label>
                <input type="time" name="heure"
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-100 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Badges récupérés</label>
                <input type="number" name="badges_recuperes" min="0" value="0"
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-100 outline-none">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeZValidateModal()"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm hover:bg-gray-50 transition">Annuler</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-violet-600 text-white rounded-xl text-sm font-medium hover:bg-violet-700 shadow-sm transition">
                    ⚡ Confirmer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const zState = {
    search:      <?= json_encode($activeSearch) ?>,
    departement: <?= json_encode(is_array($activeDept) ? ($activeDept[0] ?? '') : ($activeDept ?? '')) ?>,
    effectues:   <?= json_encode($activeEffectues) ?>,
};

const zContainer = document.getElementById('zamelaContainer');
let zDebounce;

function zBuildParams() {
    const p = new URLSearchParams();
    if (zState.search)      p.set('search', zState.search);
    if (zState.departement) p.set('departement[]', zState.departement);
    if (zState.effectues)   p.set('effectues', '1');
    p.set('ajax', '1');
    return p.toString();
}

function loadZamela(params) {
    zContainer.style.opacity = '.5';
    zContainer.style.pointerEvents = 'none';
    fetch('/zamela?' + (params ?? zBuildParams()))
        .then(r => r.text())
        .then(html => {
            zContainer.innerHTML = html;
            zContainer.style.opacity = '1';
            zContainer.style.pointerEvents = '';
        });
}

document.getElementById('zSearchInput').addEventListener('input', function () {
    zState.search = this.value.trim();
    clearTimeout(zDebounce);
    zDebounce = setTimeout(loadZamela, 380);
});
document.getElementById('zToggleFilters').addEventListener('click', () => {
    document.getElementById('zFilterPanel').classList.toggle('open');
});
document.getElementById('zDeptSelect').addEventListener('change', function () {
    zState.departement = this.value;
    this.classList.toggle('has-value', !!this.value);
    loadZamela();
});
document.getElementById('zToggleEffectues').addEventListener('click', function () {
    zState.effectues = !zState.effectues;
    this.classList.toggle('active', zState.effectues);
    loadZamela();
});

function openZValidateModal(id) {
    document.getElementById('zModalId').value = id;
    const m = document.getElementById('zValidateModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function closeZValidateModal() {
    const m = document.getElementById('zValidateModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
document.getElementById('zValidateModal').addEventListener('click', function (e) {
    if (e.target === this) closeZValidateModal();
});
</script>
