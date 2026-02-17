<?php
$current = $parcours ?? [];
$meta    = $meta ?? null;

$csrfToken = $_SESSION['csrf_token'] ?? '';
$currentVersion = $meta['updated_at'] ?? '';
?>

<!-- ===================== MODAL PRINCIPAL ===================== -->
<div id="modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">

    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-4xl p-6">

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Gestion de la maintenance</h2>

            <button onclick="openSearch()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                + Ajouter un parcours
            </button>
        </div>

        <div id="maintenanceList" class="space-y-3 max-h-96 overflow-y-auto">

            <?php if (empty($current)): ?>
                <div id="emptyMessage" class="text-gray-500 text-sm">
                    Aucun parcours en maintenance.
                </div>
            <?php else: ?>
                <?php foreach ($current as $p): ?>
                    <div class="flex justify-between items-center border rounded-lg p-3"
                         data-id="<?= (int)$p['id'] ?>">

                        <div>
                            <div class="font-medium">
                                <?= htmlspecialchars($p['titre']) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= htmlspecialchars($p['ville']) ?>
                            </div>
                        </div>

                        <button type="button"
                                onclick="confirmRemoval(<?= (int)$p['id'] ?>)"
                                class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                            Retirer
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <div class="flex justify-end gap-3 mt-6">
            <button onclick="closeModal()"
                class="px-4 py-2 border rounded-lg">
                Annuler
            </button>

            <button onclick="submitMaintenance()"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Sauvegarder
            </button>
        </div>

    </div>
</div>

<!-- ===================== MODAL RECHERCHE ===================== -->
<div id="searchModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">

    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-2xl p-6">

        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Ajouter un parcours</h3>
            <button onclick="closeSearch()">✖</button>
        </div>

        <input type="text"
               id="searchInput"
               placeholder="Rechercher par nom ou ville..."
               class="w-full border rounded-lg px-4 py-2 mb-4">

        <div id="searchResults"
             class="space-y-2 max-h-80 overflow-y-auto text-sm text-gray-600">
            Tapez pour rechercher...
        </div>

    </div>
</div>

<!-- ===================== MODAL CONFIRMATION ===================== -->
<div id="confirmModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">

    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-sm p-6">

        <h3 class="text-lg font-semibold mb-4">
            Retirer ce parcours ?
        </h3>

        <div class="flex justify-end gap-3">
            <button onclick="closeConfirm()"
                class="px-4 py-2 border rounded-lg">
                Annuler
            </button>

            <button id="confirmDeleteBtn"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Oui, retirer
            </button>
        </div>

    </div>
</div>

<style>
@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    50% { transform: translateX(4px); }
    75% { transform: translateX(-4px); }
    100% { transform: translateX(0); }
}
.shake { animation: shake 0.3s ease; }
</style>

<script>

let maintenanceIds = <?= json_encode(array_column($current, 'id')) ?>;
let pendingDeleteId = null;
let searchTimeout = null;

const csrfToken = "<?= htmlspecialchars($csrfToken) ?>";
const version   = "<?= htmlspecialchars($currentVersion) ?>";

/* ===================== SCROLL LOCK ===================== */

function lockScroll() {
    document.body.classList.add('overflow-hidden');
}

function unlockScroll() {
    document.body.classList.remove('overflow-hidden');
}

/* ===================== OPEN / CLOSE ===================== */

function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    lockScroll();
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    unlockScroll();
}

function openSearch() {
    document.getElementById('searchModal').classList.remove('hidden');
    document.getElementById('searchInput').focus();
}

function closeSearch() {
    document.getElementById('searchModal').classList.add('hidden');
}

function closeConfirm() {
    pendingDeleteId = null;
    document.getElementById('confirmModal').classList.add('hidden');
}

/* ===================== ESC ===================== */

document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") {
        closeSearch();
        closeConfirm();
        closeModal();
    }
});

/* ===================== CLICK OUTSIDE ===================== */

document.querySelectorAll('#modal, #searchModal, #confirmModal')
    .forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
                unlockScroll();
            }
        });
    });

/* ===================== ADD ===================== */

function addToMaintenanceList(p) {

    const id = parseInt(p.id);

    if (maintenanceIds.includes(id)) {
        const existing = document.querySelector(`[data-id="${id}"]`);
        if (existing) {
            existing.classList.add('shake');
            setTimeout(() => existing.classList.remove('shake'), 300);
        }
        return;
    }

    maintenanceIds.push(id);

    const list = document.getElementById('maintenanceList');
    const empty = document.getElementById('emptyMessage');
    if (empty) empty.remove();

    const item = document.createElement('div');
    item.className = `
        flex justify-between items-center border rounded-lg p-3
        opacity-0 translate-y-2 transition-all duration-300 ease-out
        bg-green-50
    `;
    item.dataset.id = id;

    item.innerHTML = `
        <div>
            <div class="font-medium">${p.titre}</div>
            <div class="text-sm text-gray-500">${p.ville}</div>
        </div>
        <button type="button"
            class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
            Retirer
        </button>
    `;

    item.querySelector('button').onclick = () => confirmRemoval(id);

    list.appendChild(item);

    requestAnimationFrame(() => {
        item.classList.remove('opacity-0', 'translate-y-2');
    });

    setTimeout(() => {
        item.classList.remove('bg-green-50');
    }, 1200);
}

/* ===================== REMOVE ===================== */

function confirmRemoval(id) {
    pendingDeleteId = id;
    document.getElementById('confirmModal').classList.remove('hidden');
}

document.getElementById('confirmDeleteBtn').onclick = function () {

    if (!pendingDeleteId) return;

    const id = pendingDeleteId;
    maintenanceIds = maintenanceIds.filter(i => i !== id);

    const element = document.querySelector(`[data-id="${id}"]`);

    if (element) {
        element.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => element.remove(), 200);
    }

    pendingDeleteId = null;
    closeConfirm();
    checkEmptyState();
};

/* ===================== EMPTY ===================== */

function checkEmptyState() {
    if (maintenanceIds.length === 0) {
        document.getElementById('maintenanceList').innerHTML = `
            <div id="emptyMessage" class="text-gray-500 text-sm">
                Aucun parcours en maintenance.
            </div>
        `;
    }
}

/* ===================== SUBMIT (avec CSRF + VERSION) ===================== */

function submitMaintenance() {

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/maintenance/update';

    // IDs
    maintenanceIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'parcours[]';
        input.value = id;
        form.appendChild(input);
    });

    // CSRF
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);

    // Version
    const versionInput = document.createElement('input');
    versionInput.type = 'hidden';
    versionInput.name = 'version';
    versionInput.value = version;
    form.appendChild(versionInput);

    document.body.appendChild(form);
    form.submit();
}

/* ===================== SEARCH ===================== */

document.getElementById('searchInput')?.addEventListener('input', function () {

    const q = this.value.trim();
    const container = document.getElementById('searchResults');

    clearTimeout(searchTimeout);

    if (q.length < 2) {
        container.innerHTML = "Tapez au moins 2 caractères...";
        return;
    }

    searchTimeout = setTimeout(() => {

        fetch('/parcours/search?q=' + encodeURIComponent(q))
            .then(response => {
                if (!response.ok) throw new Error();
                return response.json();
            })
            .then(data => {

                container.innerHTML = '';

                if (!data.length) {
                    container.innerHTML = "Aucun résultat.";
                    return;
                }

                data.forEach(p => {

                    const div = document.createElement('div');
                    div.className = "flex justify-between items-center border rounded p-2";

                    div.innerHTML = `
                        <div>
                            <div class="font-medium">${p.titre}</div>
                            <div class="text-xs text-gray-500">${p.ville}</div>
                        </div>
                        <button class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                            Ajouter
                        </button>
                    `;

                    div.querySelector('button').onclick = () => {
                        addToMaintenanceList(p);
                        closeSearch();
                    };

                    container.appendChild(div);
                });

            })
            .catch(() => {
                container.innerHTML = "Erreur lors de la recherche.";
            });

    }, 300);

});
</script>
