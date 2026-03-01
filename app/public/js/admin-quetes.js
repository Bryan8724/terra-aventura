/* ============================================================
   admin-quetes.js — Terra Aventura
   Gestion dynamique du formulaire quête (create / edit)
============================================================ */

let objetIndex        = 0;
let currentObjetIndex = null;
let initialSnapshot   = null;
let _objetToDelete    = null;

/* ─────────────────────────────────────────────────────────
   INIT
───────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {

    /* Index max des objets pré-rendus (mode edit) */
    document.querySelectorAll('[data-objet]').forEach(el => {
        const idx = parseInt(el.dataset.objet, 10);
        if (idx >= objetIndex) objetIndex = idx + 1;
        updateBadge(idx);
    });

    initialSnapshot = JSON.stringify(getFormSnapshot());
    updateSaveBtn();

    /* Écoute changements dans le formulaire */
    document.getElementById('queteForm')?.addEventListener('input',  updateSaveBtn);
    document.getElementById('queteForm')?.addEventListener('change', updateSaveBtn);

    /* Délégation : bouton +Ajouter dans les résultats de recherche */
    document.getElementById('parcoursResults')
        ?.addEventListener('click', e => {
            const btn = e.target.closest('.parcours-add-btn');
            if (!btn) return;
            try { addParcoursToObjet(JSON.parse(btn.dataset.parcours)); }
            catch(err) { console.error('Parcours data invalide', err); }
        });

    /* Placeholder vide (mode create) */
    syncEmpty();

    /* Backdrop clics pour fermer les modals */
    ['parcoursModal','deleteObjetModal','confirmSaveModal'].forEach(id => {
        document.getElementById(id)
            ?.addEventListener('click', e => { if (e.target.id === id) hideModal(id); });
    });

    /* ESC */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            hideModal('parcoursModal');
            hideModal('deleteObjetModal');
            hideModal('confirmSaveModal');
        }
    });

    /* Soumission formulaire → confirmation */
    document.getElementById('queteForm')?.addEventListener('submit', e => {
        e.preventDefault();
        if (JSON.stringify(getFormSnapshot()) === initialSnapshot) {
            taAlert('Aucune modification à enregistrer.', { icon: 'ℹ️', type: 'info' });
            return;
        }
        document.getElementById('saveSummary').innerHTML = buildSaveSummary();
        showModal('confirmSaveModal');
    });

    document.getElementById('confirmSaveBtn')?.addEventListener('click', () => {
        hideModal('confirmSaveModal');
        document.getElementById('queteForm').submit();
    });

    /* Confirmation suppression objet */
    document.getElementById('confirmDeleteObjetBtn')?.addEventListener('click', () => {
        if (_objetToDelete === null) return;
        const el = document.querySelector('[data-objet="' + _objetToDelete + '"]');
        if (el) {
            el.style.transition = 'opacity .2s, transform .2s';
            el.style.opacity    = '0';
            el.style.transform  = 'scale(.97)';
            setTimeout(() => { el.remove(); syncEmpty(); updateSaveBtn(); }, 200);
        }
        _objetToDelete = null;
        hideModal('deleteObjetModal');
    });
});

/* ─────────────────────────────────────────────────────────
   SNAPSHOT & SAVE BUTTON
───────────────────────────────────────────────────────── */
function getFormSnapshot() {
    return Array.from(document.querySelectorAll('[data-objet]')).map(o => ({
        name:     o.querySelector('input[type="text"]')?.value || '',
        parcours: Array.from(o.querySelectorAll('.parcours-list input[type="hidden"]')).map(i => i.value),
    }));
}

function updateSaveBtn() {
    const btn     = document.getElementById('saveBtn');
    if (!btn) return;
    const changed = JSON.stringify(getFormSnapshot()) !== initialSnapshot;
    btn.disabled  = !changed;
    btn.classList.toggle('opacity-40',         !changed);
    btn.classList.toggle('cursor-not-allowed', !changed);
}

/* ─────────────────────────────────────────────────────────
   OBJETS
───────────────────────────────────────────────────────── */
function addObjet() {
    const container = document.getElementById('objetsContainer');
    if (!container) return;

    const idx = objetIndex;
    const num = container.querySelectorAll('[data-objet]').length + 1;

    const html =
        '<div class="objet-card" data-objet="' + idx + '" style="opacity:0;transform:translateY(8px)">' +
            '<div class="objet-card-header">' +
                '<span class="objet-number">' + num + '</span>' +
                '<input type="text"' +
                '       name="objets[' + idx + '][nom]"' +
                '       placeholder="Nom de l\'objet…"' +
                '       required' +
                '       class="objet-name-input"' +
                '       oninput="updateSaveBtn()">' +
                '<span class="badge-parcours badge-' + idx + '">0 parcours</span>' +
                '<button type="button"' +
                '        onclick="confirmDeleteObjet(' + idx + ')"' +
                '        class="objet-delete-btn"' +
                '        title="Supprimer cet objet">🗑</button>' +
            '</div>' +
            '<div class="parcours-list"></div>' +
            '<button type="button"' +
            '        onclick="openParcoursModal(' + idx + ')"' +
            '        class="add-parcours-btn">' +
                '<span>➕</span> Ajouter un parcours' +
            '</button>' +
        '</div>';

    container.insertAdjacentHTML('beforeend', html);

    /* Animation entrée */
    const el = container.lastElementChild;
    requestAnimationFrame(() => {
        el.style.transition = 'opacity .25s, transform .25s';
        el.style.opacity    = '1';
        el.style.transform  = 'translateY(0)';
        el.querySelector('.objet-name-input')?.focus();
    });

    objetIndex++;
    syncEmpty();
    updateSaveBtn();
}

function confirmDeleteObjet(index) {
    _objetToDelete = index;
    const nom = document.querySelector('[data-objet="' + index + '"] .objet-name-input')?.value || 'cet objet';
    const span = document.getElementById('deleteObjetName');
    if (span) span.textContent = nom;
    showModal('deleteObjetModal');
}

/* ─────────────────────────────────────────────────────────
   MODAL PARCOURS
───────────────────────────────────────────────────────── */
async function openParcoursModal(index) {
    currentObjetIndex = index;
    const input   = document.getElementById('searchParcours');
    const results = document.getElementById('parcoursResults');
    if (!input || !results) return;

    showModal('parcoursModal');
    input.value        = '';
    results.innerHTML  = emptyState('🔍', 'Commencez à taper pour rechercher…');
    setTimeout(() => input.focus(), 80);

    input.oninput = debounce(async () => {
        const q = input.value.trim();
        if (q.length < 2) {
            results.innerHTML = emptyState('🔍', 'Minimum 2 caractères');
            return;
        }
        results.innerHTML = emptyState('⏳', 'Recherche…');
        try {
            const res  = await fetch('/admin/quetes/search-parcours?q=' + encodeURIComponent(q));
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); }
            catch {
                results.innerHTML = errorState('Réponse non-JSON (HTTP ' + res.status + ')', text.substring(0, 300));
                return;
            }
            if (!res.ok) {
                results.innerHTML = errorState(data?.message || 'HTTP ' + res.status + ' ' + res.statusText);
                return;
            }
            const list = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : null);
            if (!list) {
                results.innerHTML = errorState('Format inattendu', JSON.stringify(data).substring(0, 200));
                return;
            }
            renderParcoursResults(list);
        } catch(err) {
            results.innerHTML = errorState(err.message || 'Erreur réseau inconnue');
        }
    }, 280);
}

function closeParcoursModal() {
    hideModal('parcoursModal');
    currentObjetIndex = null;
}

function renderParcoursResults(data) {
    const results = document.getElementById('parcoursResults');
    if (!data.length) {
        results.innerHTML = emptyState('🗺️', 'Aucun parcours trouvé');
        return;
    }

    const existing = new Set(
        Array.from(document.querySelectorAll('[data-objet="' + currentObjetIndex + '"] .parcours-list input[type="hidden"]'))
            .map(i => String(i.value))
    );

    results.innerHTML = data.map(p => {
        const already = existing.has(String(p.id));
        const logo = p.logo
            ? '<img src="' + esc(p.logo) + '" class="parcours-result-logo" alt="" loading="lazy">'
            : '<div class="parcours-result-logo-ph">📍</div>';
        const action = already
            ? '<span class="text-xs text-slate-400 italic flex-shrink-0">Déjà ajouté</span>'
            : '<button type="button" class="parcours-add-btn" data-parcours="' + esc(JSON.stringify(p)) + '">+ Ajouter</button>';
        return (
            '<div class="parcours-result-item ' + (already ? 'already' : '') + '">' +
                logo +
                '<div class="flex-1 min-w-0">' +
                    '<p class="text-sm font-semibold text-slate-800 truncate">' + esc(p.titre) + '</p>' +
                    '<p class="text-xs text-slate-400">' + esc(p.ville) + ' (' + esc(p.departement_code) + ')</p>' +
                '</div>' +
                action +
            '</div>'
        );
    }).join('');
}

/* ─────────────────────────────────────────────────────────
   PARCOURS
───────────────────────────────────────────────────────── */
function addParcoursToObjet(p) {
    const objet = document.querySelector('[data-objet="' + currentObjetIndex + '"]');
    if (!objet) return;
    const list = objet.querySelector('.parcours-list');

    if (list.querySelector('input[value="' + p.id + '"]')) {
        taAlert('Ce parcours est déjà ajouté à cet objet.', { icon: 'ℹ️', type: 'info' });
        return;
    }

    const logo = p.logo
        ? '<img src="' + esc(p.logo) + '" class="parcours-item-logo" alt="" loading="lazy">'
        : '<div class="parcours-item-logo-ph">📍</div>';

    const html =
        '<div class="parcours-item-row" style="opacity:0;transform:translateX(-6px)">' +
            logo +
            '<div class="flex-1 min-w-0">' +
                '<p class="text-sm font-semibold text-slate-700 truncate">' + esc(p.titre) + '</p>' +
                '<p class="text-xs text-slate-400">' + esc(p.ville) + ' (' + esc(p.departement_code) + ')</p>' +
            '</div>' +
            '<button type="button" onclick="removeParcours(this)" class="parcours-remove-btn" title="Retirer">✕</button>' +
            '<input type="hidden" name="objets[' + currentObjetIndex + '][parcours][]" value="' + esc(String(p.id)) + '">' +
        '</div>';

    list.insertAdjacentHTML('beforeend', html);
    const row = list.lastElementChild;
    requestAnimationFrame(() => {
        row.style.transition = 'opacity .2s, transform .2s';
        row.style.opacity    = '1';
        row.style.transform  = 'translateX(0)';
    });

    updateBadge(currentObjetIndex);
    closeParcoursModal();
    updateSaveBtn();
}

function removeParcours(btn) {
    const row   = btn.closest('.parcours-item-row');
    const objet = btn.closest('[data-objet]');
    if (!row || !objet) return;
    row.style.transition = 'opacity .15s, transform .15s';
    row.style.opacity    = '0';
    row.style.transform  = 'translateX(-6px)';
    setTimeout(() => {
        row.remove();
        updateBadge(objet.dataset.objet);
        updateSaveBtn();
    }, 150);
}

function updateBadge(index) {
    const el    = document.querySelector('[data-objet="' + index + '"]');
    const badge = el?.querySelector('.badge-' + index);
    if (!badge) return;
    const count = el.querySelectorAll('.parcours-list input[type="hidden"]').length;
    badge.textContent = count + ' parcours';
    badge.classList.toggle('badge-has', count > 0);
}

/* ─────────────────────────────────────────────────────────
   MODAL CONFIRM SAVE
───────────────────────────────────────────────────────── */
function buildSaveSummary() {
    const before = JSON.parse(initialSnapshot);
    const after  = getFormSnapshot();
    let   html   = '';

    after.forEach((curr, i) => {
        const prev  = before[i];
        const objEl = document.querySelector('[data-objet="' + i + '"]');

        if (!prev) {
            html += '<div class="summary-item new"><span class="summary-icon">➕</span><div>' +
                    '<strong>' + esc(curr.name || 'Objet sans nom') + '</strong>' +
                    '<p class="text-xs mt-0.5">' + curr.parcours.length + ' parcours</p></div></div>';
            return;
        }

        const added   = curr.parcours.filter(id => !prev.parcours.includes(id));
        const removed = prev.parcours.filter(id => !curr.parcours.includes(id));
        const renamed = prev.name !== curr.name;
        if (!added.length && !removed.length && !renamed) return;

        const map = {};
        objEl?.querySelectorAll('.parcours-item-row').forEach(row => {
            const id    = row.querySelector('input[type="hidden"]')?.value;
            const title = row.querySelector('p.font-semibold')?.textContent || '';
            const sub   = row.querySelector('p.text-xs')?.textContent || '';
            if (id) map[id] = title + (sub ? ' — ' + sub : '');
        });

        html += '<div class="summary-item"><span class="summary-icon">✏️</span><div class="flex-1 min-w-0">' +
                '<strong>' + esc(prev.name || 'Objet sans nom') + '</strong>' +
                (renamed ? '<p class="text-xs mt-0.5 text-slate-500">Renommé : <s>' + esc(prev.name) + '</s> → <b>' + esc(curr.name) + '</b></p>' : '') +
                (added.length   ? '<p class="text-xs text-green-700 mt-0.5">➕ ' + added.map(id => esc(map[id] || 'Parcours #' + id)).join(', ') + '</p>' : '') +
                (removed.length ? '<p class="text-xs text-red-600 mt-0.5">✕ ' + removed.map(id => esc(map[id] || 'Parcours #' + id)).join(', ') + '</p>' : '') +
                '</div></div>';
    });

    before.forEach((prev, i) => {
        if (!after[i]) {
            html += '<div class="summary-item deleted"><span class="summary-icon">🗑</span><div>' +
                    '<strong>' + esc(prev.name || 'Objet sans nom') + '</strong>' +
                    '<p class="text-xs mt-0.5 text-red-500">Supprimé</p></div></div>';
        }
    });

    return html || '<div class="text-slate-400 italic text-sm">Aucune modification détectée</div>';
}

/* ─────────────────────────────────────────────────────────
   MODALS
───────────────────────────────────────────────────────── */
function showModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}
function hideModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}
/* Aliases appelés depuis les onclick HTML */
function closeDeleteObjetModal()  { hideModal('deleteObjetModal');  }
function closeConfirmSaveModal()  { hideModal('confirmSaveModal');  }
function closeParcoursModal()     { hideModal('parcoursModal'); currentObjetIndex = null; }

/* ─────────────────────────────────────────────────────────
   PLACEHOLDER VIDE (mode create)
───────────────────────────────────────────────────────── */
function syncEmpty() {
    const empty     = document.getElementById('objetsEmpty');
    const container = document.getElementById('objetsContainer');
    if (!empty || !container) return;
    const has = container.querySelectorAll('[data-objet]').length > 0;
    empty.style.display     = has ? 'none' : '';
    container.style.display = has ? ''     : 'none';
}

/* ─────────────────────────────────────────────────────────
   UTILITAIRES
───────────────────────────────────────────────────────── */
function esc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function emptyState(icon, text) {
    return '<div class="parcours-empty">' + icon + ' ' + esc(text) + '</div>';
}

function errorState(message, detail) {
    return '<div style="padding:1rem;display:flex;flex-direction:column;gap:.5rem">' +
        '<div style="display:flex;align-items:center;gap:.5rem;color:#dc2626;font-size:.85rem;font-weight:600">⚠️ Erreur lors de la recherche</div>' +
        '<div style="font-size:.78rem;color:#b91c1c;background:#fff5f5;border:1px solid #fecaca;border-radius:.5rem;padding:.5rem .75rem;font-family:monospace;word-break:break-all">' +
            esc(message) +
        '</div>' +
        (detail
            ? '<details style="font-size:.72rem;color:#94a3b8"><summary style="cursor:pointer;color:#64748b">Détails techniques</summary>' +
              '<pre style="margin-top:.4rem;white-space:pre-wrap;word-break:break-all;background:#f8fafc;padding:.5rem;border-radius:.4rem;border:1px solid #e2e8f0">' + esc(detail) + '</pre></details>'
            : '') +
    '</div>';
}

function debounce(fn, ms) {
    let t;
    return function() {
        const args = arguments;
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), ms);
    };
}
