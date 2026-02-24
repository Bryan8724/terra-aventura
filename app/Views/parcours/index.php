<?php
$user    = $_SESSION['user'] ?? null;
$isAdmin = isset($user['role']) && $user['role'] === 'admin';
$currentPage = max(1, (int)($_GET['page'] ?? 1));

$activeSearch      = trim($_GET['search'] ?? '');
$activeDept        = $_GET['departement'] ?? [];
$activePoiz        = $_GET['poiz_id'] ?? '';
$activeEffectues   = isset($_GET['effectues']);

$hasFilters = $activeSearch !== '' || !empty($activeDept) || $activePoiz !== '' || $activeEffectues;
?>

<style>
.search-wrapper { position: relative; }
.search-icon {
    position: absolute; left: 1rem; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; pointer-events: none; font-size: 1rem;
}
#searchInput { padding-left: 2.75rem; transition: box-shadow .2s, border-color .2s; }
#searchInput:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); outline: none; }

.filter-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .9rem; border-radius: 9999px;
    border: 1.5px solid #e2e8f0; background: #fff;
    font-size: .8rem; font-weight: 500; color: #475569;
    cursor: pointer; transition: all .18s; white-space: nowrap; user-select: none;
}
.filter-pill:hover { border-color: #93c5fd; color: #1d4ed8; background: #eff6ff; }
.filter-pill.active { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
.filter-pill.active:hover { background: #1e40af; }

.filter-select {
    padding: .45rem 2.2rem .45rem .9rem;
    border-radius: 9999px; border: 1.5px solid #e2e8f0; background: #fff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: calc(100% - .65rem) center;
    appearance: none; font-size: .8rem; font-weight: 500; color: #475569;
    cursor: pointer; transition: all .18s;
}
.filter-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
.filter-select.has-value { background-color: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }

.btn-reset {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .45rem .9rem; border-radius: 9999px;
    border: 1.5px solid #fca5a5; background: #fff;
    color: #dc2626; font-size: .8rem; font-weight: 500;
    text-decoration: none; cursor: pointer; transition: all .18s;
}
.btn-reset:hover { background: #fef2f2; border-color: #dc2626; }

#filterPanel {
    overflow: hidden; transition: max-height .3s ease, opacity .3s ease;
    max-height: 0; opacity: 0;
}
#filterPanel.open { max-height: 300px; opacity: 1; }
</style>

<!-- HEADER -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Parcours</h1>
        <p class="text-sm text-gray-500">Liste des parcours disponibles et effectués</p>
    </div>
    <?php if ($isAdmin): ?>
        <a href="/parcours/create"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 shadow-sm transition">
            ➕ Ajouter
        </a>
    <?php endif; ?>
</div>

<!-- BARRE RECHERCHE + FILTRES -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">

    <div class="p-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

        <!-- Recherche -->
        <div class="search-wrapper flex-1">
            <span class="search-icon">🔍</span>
            <input type="text" id="searchInput"
                   value="<?= htmlspecialchars($activeSearch) ?>"
                   placeholder="Rechercher un parcours, une ville…"
                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400">
        </div>

        <!-- Bouton filtres -->
        <button id="toggleFilters" type="button"
                class="filter-pill shrink-0 <?= (!empty($activeDept) || $activePoiz) ? 'active' : '' ?>">
            ⚙️ Filtres
            <?php $nbFiltres = (count((array)$activeDept) + (!empty($activePoiz) ? 1 : 0)); ?>
            <?php if ($nbFiltres > 0): ?>
                <span class="ml-1 w-5 h-5 flex items-center justify-center rounded-full bg-white/25 text-xs font-bold">
                    <?= $nbFiltres ?>
                </span>
            <?php endif; ?>
        </button>

        <!-- Pill Effectués -->
        <button id="toggleEffectues" type="button"
                class="filter-pill shrink-0 <?= $activeEffectues ? 'active' : '' ?>">
            ✔ Effectués
        </button>

        <!-- Reset -->
        <?php if ($hasFilters): ?>
            <a href="/parcours" class="btn-reset shrink-0">✕ Réinitialiser</a>
        <?php endif; ?>
    </div>

    <!-- Panneau filtres avancés -->
    <div id="filterPanel" class="<?= (!empty($activeDept) || $activePoiz) ? 'open' : '' ?>">
        <div class="px-4 pb-4 pt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-gray-100">

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide pl-1">Département</label>
                <select id="deptSelect" class="filter-select w-full <?= !empty($activeDept) ? 'has-value' : '' ?>">
                    <option value="">Tous les départements</option>
                    <?php foreach ($departements as $code => $nom): ?>
                        <option value="<?= $code ?>"
                            <?= (is_array($activeDept) && in_array((string)$code, array_map('strval', $activeDept))) ? 'selected' : '' ?>>
                            <?= $code ?> — <?= htmlspecialchars($nom) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-400 uppercase tracking-wide pl-1">POIZ</label>
                <select id="poizSelect" class="filter-select w-full <?= $activePoiz ? 'has-value' : '' ?>">
                    <option value="">Tous les POIZ</option>
                    <?php foreach ($poiz as $pz): ?>
                        <option value="<?= (int)$pz['id'] ?>"
                            <?= (string)$activePoiz === (string)$pz['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pz['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>
    </div>
</div>

<!-- LISTE -->
<div id="parcoursContainer">
    <?php require __DIR__ . '/_list.php'; ?>
</div>

<!-- MODAL VALIDATION -->
<div id="validateModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">

        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold text-gray-800">✔ Valider ce parcours</h2>
            <button type="button" onclick="closeValidateModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 text-2xl leading-none">
                ×
            </button>
        </div>

        <form method="post" action="/parcours/valider" class="space-y-4">
            <input type="hidden" name="parcours_id" id="modalParcoursId">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de réalisation</label>
                <input type="date" name="date" required
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Heure <span class="text-gray-400 font-normal">(optionnel)</span>
                </label>
                <input type="time" name="heure"
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Badges récupérés</label>
                <input type="number" name="badges_recuperes" min="0" value="0"
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none">
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeValidateModal()"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm hover:bg-gray-50 transition">
                    Annuler
                </button>
                <button type="submit"
                        class="px-5 py-2.5 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 shadow-sm transition">
                    ✔ Confirmer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const state = {
    search:      <?= json_encode($activeSearch) ?>,
    departement: <?= json_encode(is_array($activeDept) ? ($activeDept[0] ?? '') : ($activeDept ?? '')) ?>,
    poiz_id:     <?= json_encode($activePoiz) ?>,
    effectues:   <?= json_encode($activeEffectues) ?>,
};

const container = document.getElementById('parcoursContainer');
let debounce;

function buildParams() {
    const p = new URLSearchParams();
    if (state.search)      p.set('search', state.search);
    if (state.departement) p.set('departement[]', state.departement);
    if (state.poiz_id)     p.set('poiz_id', state.poiz_id);
    if (state.effectues)   p.set('effectues', '1');
    p.set('ajax', '1');
    return p.toString();
}

function loadParcours(params) {
    container.style.opacity = '.5';
    container.style.pointerEvents = 'none';
    fetch('/parcours?' + (params ?? buildParams()))
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            container.style.pointerEvents = '';
        });
}

/* Recherche */
document.getElementById('searchInput').addEventListener('input', function () {
    state.search = this.value.trim();
    clearTimeout(debounce);
    debounce = setTimeout(loadParcours, 380);
});

/* Toggle filtres */
document.getElementById('toggleFilters').addEventListener('click', () => {
    document.getElementById('filterPanel').classList.toggle('open');
});

/* Département */
document.getElementById('deptSelect').addEventListener('change', function () {
    state.departement = this.value;
    this.classList.toggle('has-value', !!this.value);
    loadParcours();
});

/* POIZ */
document.getElementById('poizSelect').addEventListener('change', function () {
    state.poiz_id = this.value;
    this.classList.toggle('has-value', !!this.value);
    loadParcours();
});

/* Effectués */
document.getElementById('toggleEffectues').addEventListener('click', function () {
    state.effectues = !state.effectues;
    this.classList.toggle('active', state.effectues);
    loadParcours();
});

/* Modal */
function openValidateModal(id) {
    document.getElementById('modalParcoursId').value = id;
    const m = document.getElementById('validateModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function closeValidateModal() {
    const m = document.getElementById('validateModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}
document.getElementById('validateModal').addEventListener('click', function (e) {
    if (e.target === this) closeValidateModal();
});
</script>
