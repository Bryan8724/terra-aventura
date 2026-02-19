<?php

$meta       = $meta ?? null;
$parcours   = $parcours ?? [];
$csrfToken  = $_SESSION['csrf_token'] ?? '';

$lastUpdate = null;

if (!empty($meta['updated_at'])) {
    try {
        $dt = new DateTime($meta['updated_at'], new DateTimeZone('Europe/Paris'));
        $lastUpdate = $dt->format('d/m/Y à H:i');
    } catch (Exception $e) {}
}
?>

<!-- ================= HEADER ================= -->

<div class="flex justify-between items-center mb-8">

    <div>
        <h1 class="text-2xl font-semibold flex items-center gap-3">
            Maintenance des parcours
            <span id="countBadge"
                  class="text-sm bg-red-100 text-red-700 px-3 py-1 rounded-full">
                <?= count($parcours) ?> actif(s)
            </span>
        </h1>

        <?php if ($lastUpdate): ?>
            <p class="text-sm text-gray-500 mt-1">
                Dernière modification le <?= $lastUpdate ?>
                <?php if (!empty($meta['username'])): ?>
                    par <?= htmlspecialchars($meta['username']) ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-4">
        <button onclick="openHistoryModal()"
                class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-black transition">
            Historique
        </button>

        <button onclick="openModal()"
                class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow">
            Éditer la liste
        </button>
    </div>

</div>

<!-- ================= LISTE ================= -->

<div id="parcoursContainer" class="space-y-4"></div>
<div id="paginationContainer" class="flex justify-center gap-2 mt-8"></div>

<!-- ================= MODAL HISTORIQUE ================= -->

<div id="historyModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-8">

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Historique des modifications</h2>
            <button onclick="closeHistoryModal()">✖</button>
        </div>

        <div id="historyContainer"
             class="space-y-4 max-h-[400px] overflow-y-auto"></div>

        <div id="historyPagination"
             class="flex justify-center gap-2 mt-6"></div>

    </div>
</div>

<!-- ================= MODAL DÉTAIL PREMIUM ================= -->

<div id="parcoursModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">

    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden animate-fadeIn">

        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white">
            <h2 id="modalTitre" class="text-2xl font-bold"></h2>
        </div>

        <div id="modalContent"
             class="p-8 grid grid-cols-2 gap-6 text-gray-700"></div>

        <div class="flex justify-end p-6 border-t bg-gray-50">
            <button onclick="closeParcoursModal()"
                    class="px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">
                Fermer
            </button>
        </div>

    </div>
</div>

<?php require '_modal.php'; ?>

<script>

/* ================= PARCOURS ================= */

async function loadParcours(page = 1) {

    const res = await fetch(`/maintenance/ajaxParcours?page=${page}`);
    const json = await res.json();

    const container = document.getElementById('parcoursContainer');
    container.innerHTML = '';

    json.data.forEach(p => {
        container.innerHTML += renderCard(p);
    });

    renderPagination(json.page, json.totalPages, loadParcours, 'paginationContainer');
}

function renderCard(p) {
    return `
        <div class="bg-red-50 border border-red-200 p-6 rounded-xl shadow-sm flex items-center justify-between hover:shadow-md transition">

            <div class="flex items-center gap-4">

                ${p.poiz_logo ? `<img src="${p.poiz_logo}" class="w-14 h-14 object-contain rounded">` : ''}

                <div>
                    <div class="text-lg font-semibold text-red-700">${p.titre}</div>
                    <div class="text-sm text-gray-600">
                        📍 ${p.ville ?? ''} (${p.departement_code ?? ''})
                    </div>
                </div>

            </div>

            <button onclick='openParcoursModal(${JSON.stringify(p)})'
                class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-black transition">
                Voir parcours
            </button>
        </div>
    `;
}

/* ================= HISTORIQUE (AVEC DIFF) ================= */

async function loadHistory(page = 1) {

    const res = await fetch(`/maintenance/ajaxHistoryDiff?page=${page}`);
    const json = await res.json();

    const container = document.getElementById('historyContainer');
    container.innerHTML = '';

    json.data.forEach(h => {

        let changes = '';

        h.added.forEach(a => {
            changes += `<div class="text-green-600 text-sm">+ ${a} ajouté</div>`;
        });

        h.removed.forEach(r => {
            changes += `<div class="text-red-600 text-sm">− ${r} retiré</div>`;
        });

        if (!changes) {
            changes = `<div class="text-gray-400 text-sm">Aucun changement</div>`;
        }

        container.innerHTML += `
            <div class="bg-gray-50 border rounded-xl p-4 space-y-2">
                <div class="text-sm font-medium text-gray-600">
                    ${h.updated_at} — ${h.username}
                </div>
                ${changes}
            </div>
        `;
    });

    renderPagination(json.page, json.totalPages, loadHistory, 'historyPagination');
}

/* ================= PAGINATION ================= */

function renderPagination(current, total, callback, containerId) {

    const container = document.getElementById(containerId);
    container.innerHTML = '';

    for (let i = 1; i <= total; i++) {
        container.innerHTML += `
            <button onclick="${callback.name}(${i})"
                class="px-4 py-2 rounded-lg border
                ${i === current
                    ? 'bg-indigo-600 text-white border-indigo-600'
                    : 'bg-white hover:bg-indigo-50 border-gray-300'}">
                ${i}
            </button>
        `;
    }
}

/* ================= MODALS ================= */

function openHistoryModal() {
    document.getElementById('historyModal').classList.remove('hidden');
    loadHistory();
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

function openParcoursModal(data) {

    document.getElementById('parcoursModal').classList.remove('hidden');
    document.getElementById('modalTitre').innerText = data.titre ?? '';

    document.getElementById('modalContent').innerHTML = `
        ${data.poiz_logo ? `
            <div class="col-span-2 flex justify-center mb-6">
                <img src="${data.poiz_logo}" class="w-28 h-28 object-contain">
            </div>
        ` : ''}

        <div class="bg-gray-50 p-4 rounded-xl">
            <div class="text-xs text-gray-400 uppercase">Ville</div>
            <div class="font-semibold">${data.ville ?? '—'}</div>
        </div>

        <div class="bg-gray-50 p-4 rounded-xl">
            <div class="text-xs text-gray-400 uppercase">Département</div>
            <div class="font-semibold">${data.departement_code ?? '—'}</div>
        </div>

        <div class="bg-indigo-50 p-4 rounded-xl">
            <div class="text-xs text-indigo-400 uppercase">Distance</div>
            <div class="font-semibold">${data.distance_km ?? '—'} km</div>
        </div>

        <div class="bg-purple-50 p-4 rounded-xl">
            <div class="text-xs text-purple-400 uppercase">Durée</div>
            <div class="font-semibold">${data.duree ?? '—'}</div>
        </div>

        <div class="bg-gray-100 p-4 rounded-xl">
            <div class="text-xs text-gray-400 uppercase">Niveau</div>
            <div class="font-semibold">${data.niveau ?? '—'}</div>
        </div>

        <div class="bg-gray-100 p-4 rounded-xl">
            <div class="text-xs text-gray-400 uppercase">Terrain</div>
            <div class="font-semibold">${data.terrain ?? '—'}</div>
        </div>
    `;
}

function closeParcoursModal() {
    document.getElementById('parcoursModal').classList.add('hidden');
}

/* INIT */
loadParcours();

</script>
