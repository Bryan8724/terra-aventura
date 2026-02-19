let objetIndex = 0;
let currentObjetIndex = null;
let objetToDelete = null;

let initialSnapshot = null;
let formHasChanged = false;

/* =========================
   INIT INDEX + SNAPSHOT
========================= */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-objet]').forEach(el => {
        const idx = parseInt(el.dataset.objet, 10);
        if (idx >= objetIndex) objetIndex = idx + 1;
        updateBadge(idx);
    });

    initialSnapshot = JSON.stringify(getFormSnapshotParsed());
    updateSaveButtonState();

    const form = document.getElementById('queteForm');
    form.addEventListener('input', onFormChange);
    form.addEventListener('change', onFormChange);
});

/* =========================
   SNAPSHOT & CHANGES
========================= */
function getFormSnapshotParsed() {
    const objets = [];

    document.querySelectorAll('[data-objet]').forEach(o => {
        const name = o.querySelector('input[type="text"]')?.value || '';
        const parcours = Array.from(
            o.querySelectorAll('.parcours-list input')
        ).map(i => i.value);

        objets.push({ name, parcours });
    });

    return objets;
}

function onFormChange() {
    const current = JSON.stringify(getFormSnapshotParsed());
    formHasChanged = current !== initialSnapshot;
    updateSaveButtonState();
}

function updateSaveButtonState() {
    const btn = document.getElementById('saveBtn');
    if (!btn) return;

    if (!formHasChanged) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.title = 'Aucune modification détectée';
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        btn.title = '';
    }
}

/* =========================
   TOAST
========================= */
function showToast(message, type = 'error') {
    const container = document.querySelector('.toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 500);
    }, 3500);
}

/* =========================
   OBJETS
========================= */
function addObjet() {
    const container = document.getElementById('objetsContainer');

    container.insertAdjacentHTML('beforeend', `
        <div class="bg-white border rounded-lg p-4 space-y-3"
             data-objet="${objetIndex}">

            <div class="flex items-center justify-between gap-4">
                <input type="text"
                       name="objets[${objetIndex}][nom]"
                       placeholder="Nom de l’objet"
                       required
                       class="w-full border rounded px-3 py-2">

                <div class="flex items-center gap-3">
                    <span class="badge-${objetIndex} text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">
                        0 parcours
                    </span>

                    <button type="button"
                            onclick="confirmDeleteObjet(${objetIndex})"
                            class="text-red-600 hover:text-red-800">
                        🗑
                    </button>
                </div>
            </div>

            <div class="parcours-list space-y-2 pl-2"></div>

            <button type="button"
                    onclick="openParcoursModal(${objetIndex})"
                    class="text-blue-600 text-sm font-medium">
                ➕ Ajouter un parcours
            </button>
        </div>
    `);

    objetIndex++;
    onFormChange();
}

/* =========================
   SUPPRESSION OBJET (MODAL)
========================= */
function confirmDeleteObjet(index) {
    objetToDelete = index;
    const modal = document.getElementById('deleteObjetModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDeleteObjetModal() {
    objetToDelete = null;
    const modal = document.getElementById('deleteObjetModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('confirmDeleteObjetBtn')
    ?.addEventListener('click', () => {
        if (objetToDelete === null) return;

        document
            .querySelector(`[data-objet="${objetToDelete}"]`)
            ?.remove();

        objetToDelete = null;
        closeDeleteObjetModal();
        onFormChange();
    });

/* =========================
   MODAL PARCOURS
========================= */
async function openParcoursModal(index) {
    currentObjetIndex = index;

    const modal   = document.getElementById('parcoursModal');
    const input   = document.getElementById('searchParcours');
    const results = document.getElementById('parcoursResults');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    input.value = '';
    results.innerHTML = '';
    input.focus();

    input.oninput = async () => {
        const q = input.value.trim();
        if (q.length < 2) {
            results.innerHTML = '';
            return;
        }

        try {
            const res = await fetch(
                `/admin/quetes/search-parcours?q=${encodeURIComponent(q)}`
            );
            if (!res.ok) throw new Error();

            const data = await res.json();
            if (!Array.isArray(data)) throw new Error();

            results.innerHTML = '';

            if (!data.length) {
                results.innerHTML =
                    `<div class="text-gray-400">Aucun parcours trouvé</div>`;
                return;
            }

            data.forEach(p => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 border-b py-2';

                div.innerHTML = `
                    <img src="${p.logo}" class="w-10 h-10 object-contain">
                    <div class="flex-1">
                        <strong>${p.titre}</strong><br>
                        <span class="text-xs text-gray-600">
                            ${p.ville} (${p.departement_code})
                        </span>
                    </div>
                    <button class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm">
                        Ajouter
                    </button>
                `;

                div.querySelector('button')
                    .addEventListener('click', () => addParcoursToObjet(p));

                results.appendChild(div);
            });

        } catch {
            showToast('Erreur lors de la recherche des parcours', 'error');
        }
    };
}

function closeParcoursModal() {
    const modal = document.getElementById('parcoursModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

/* =========================
   PARCOURS
========================= */
function addParcoursToObjet(p) {
    const objet = document.querySelector(`[data-objet="${currentObjetIndex}"]`);
    if (!objet) return;

    const list = objet.querySelector('.parcours-list');

    if (list.querySelector(`input[value="${p.id}"]`)) {
        showToast('Ce parcours est déjà ajouté', 'warning');
        return;
    }

    list.insertAdjacentHTML('beforeend', `
        <div class="flex items-center justify-between gap-3 bg-gray-50 border rounded p-2">
            <div class="flex items-center gap-3">
                <img src="${p.logo}" class="w-8 h-8 object-contain">
                <div class="leading-tight">
                    <strong>${p.titre}</strong><br>
                    <span class="text-xs text-gray-600">
                        ${p.ville} (${p.departement_code})
                    </span>
                </div>
            </div>

            <button type="button"
                    onclick="removeParcours(this)"
                    class="text-red-600 hover:text-red-800 text-sm">
                ✖
            </button>

            <input type="hidden"
                   name="objets[${currentObjetIndex}][parcours][]"
                   value="${p.id}">
        </div>
    `);

    updateBadge(currentObjetIndex);
    closeParcoursModal();
    onFormChange();
}

function removeParcours(btn) {
    const objet = btn.closest('[data-objet]');
    if (!objet) return;

    btn.closest('div').remove();
    updateBadge(objet.dataset.objet);
    onFormChange();
}

/* =========================
   BADGE PARCOURS
========================= */
function updateBadge(index) {
    const el = document.querySelector(`[data-objet="${index}"]`);
    if (!el) return;

    const count = el.querySelectorAll('.parcours-list input').length;
    const badge = el.querySelector(`.badge-${index}`);

    if (badge) {
        badge.textContent = `${count} parcours`;
    }
}

/* =========================
   CONFIRMATION AVANT SUBMIT
========================= */
document.getElementById('queteForm')
    ?.addEventListener('submit', e => {
        e.preventDefault();

        if (!formHasChanged) {
            showToast('Aucune modification à enregistrer', 'warning');
            return;
        }

        document.getElementById('saveSummary').innerHTML = buildSaveSummary();
        openConfirmSaveModal();
    });

function getParcoursMapFromDOM(objetEl) {
    const map = {};

    objetEl.querySelectorAll('.parcours-list > div').forEach(div => {
        const input = div.querySelector('input[type="hidden"]');
        if (!input) return;

        const id = input.value;
        const title = div.querySelector('strong')?.textContent || 'Parcours';
        const subtitle = div.querySelector('.text-xs')?.textContent || '';

        map[id] = `${title}${subtitle ? ' — ' + subtitle : ''}`;
    });

    return map;
}

function buildSaveSummary() {
    const before = JSON.parse(initialSnapshot);
    const after = getFormSnapshotParsed();

    let html = '';

    after.forEach((curr, i) => {
        const prev = before[i];
        const objetEl = document.querySelector(`[data-objet="${i}"]`);

        if (!prev) {
            html += `
                <div class="border rounded p-3 bg-green-50">
                    <strong>${curr.name || 'Objet sans nom'}</strong>
                    <div class="text-sm text-green-700">
                        ➕ Nouvel objet (${curr.parcours.length} parcours)
                    </div>
                </div>
            `;
            return;
        }

        const beforeIds = prev.parcours.map(String);
        const afterIds  = curr.parcours.map(String);

        const added   = afterIds.filter(id => !beforeIds.includes(id));
        const removed = beforeIds.filter(id => !afterIds.includes(id));
        const renamed = prev.name !== curr.name;

        if (!added.length && !removed.length && !renamed) return;

        const map = objetEl ? getParcoursMapFromDOM(objetEl) : {};

        html += `
            <div class="border rounded p-3 bg-gray-50 space-y-2">
                <strong>${prev.name || 'Objet sans nom'}</strong>

                ${renamed ? `
                    <div class="text-sm">
                        Nom :
                        <span class="line-through text-gray-400">${prev.name}</span>
                        →
                        <span class="font-medium text-blue-700">${curr.name}</span>
                    </div>
                ` : ''}

                <div class="text-sm">
                    Parcours :
                    ${beforeIds.length}
                    →
                    ${afterIds.length}
                </div>

                ${added.length ? `
                    <div class="text-sm text-green-700">
                        <strong>Ajoutés :</strong>
                        <ul class="list-disc list-inside">
                            ${added.map(id => `<li>+ ${map[id] || 'Parcours #' + id}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}

                ${removed.length ? `
                    <div class="text-sm text-red-700">
                        <strong>Supprimés :</strong>
                        <ul class="list-disc list-inside">
                            ${removed.map(id => `<li>- ${map[id] || 'Parcours #' + id}</li>`).join('')}
                        </ul>
                    </div>
                ` : ''}
            </div>
        `;
    });

    return html || `<div class="text-gray-400 italic">Aucune modification détectée</div>`;
}

function openConfirmSaveModal() {
    const modal = document.getElementById('confirmSaveModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeConfirmSaveModal() {
    const modal = document.getElementById('confirmSaveModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

document.getElementById('confirmSaveBtn')
    ?.addEventListener('click', () => {
        closeConfirmSaveModal();
        document.getElementById('queteForm').submit();
    });
