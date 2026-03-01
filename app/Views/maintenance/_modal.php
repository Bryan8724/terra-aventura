<?php
$current        = $parcours ?? [];
$meta           = $meta ?? null;
$csrfToken      = $_SESSION['csrf_token'] ?? '';
$currentVersion = $meta['updated_at'] ?? '';
?>

<style>
/* DRAWER */
#drawerOverlay{position:fixed;inset:0;z-index:60;background:rgba(15,23,42,.45);backdrop-filter:blur(2px);opacity:0;pointer-events:none;transition:opacity .25s ease}
#drawerOverlay.open{opacity:1;pointer-events:all}
#drawer{position:fixed;top:0;right:0;bottom:0;z-index:61;width:min(920px,96vw);background:#f8fafc;box-shadow:-8px 0 48px rgba(0,0,0,.18);display:flex;flex-direction:column;transform:translateX(100%);transition:transform .28s cubic-bezier(.4,0,.2,1)}
#drawer.open{transform:translateX(0)}
/* HEADER */
.drw-head{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.5rem;background:#fff;border-bottom:1px solid #e2e8f0;flex-shrink:0;gap:1rem}
.drw-title{font-size:1.05rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:.5rem}
.drw-close{width:2.2rem;height:2.2rem;display:flex;align-items:center;justify-content:center;border-radius:.65rem;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:1.15rem;cursor:pointer;transition:all .15s;flex-shrink:0;font-weight:400;line-height:1}
.drw-close:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626}
/* CORPS */
.drw-body{display:grid;grid-template-columns:1fr 1fr;flex:1;overflow:hidden;min-height:0}
/* COL GAUCHE */
.drw-col-left{display:flex;flex-direction:column;background:#fff;border-right:1px solid #e2e8f0;overflow:hidden}
.drw-col-head{padding:.75rem 1.1rem;font-size:.72rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#94a3b8;border-bottom:1px solid #f1f5f9;flex-shrink:0;display:flex;align-items:center;justify-content:space-between}
.drw-list{overflow-y:auto;flex:1;padding:.65rem;display:flex;flex-direction:column;gap:.4rem}
/* ITEM */
.drw-item{display:flex;align-items:center;gap:.65rem;padding:.65rem .85rem;background:#fff;border:1.5px solid #e2e8f0;border-radius:.85rem;transition:all .18s}
.drw-item:hover{border-color:#fca5a5;background:#fff9f9}
.drw-item-num{width:1.65rem;height:1.65rem;flex-shrink:0;border-radius:50%;background:#fee2e2;color:#b91c1c;font-size:.7rem;font-weight:800;display:flex;align-items:center;justify-content:center}
.drw-item-info{flex:1;min-width:0}
.drw-item-name{font-size:.85rem;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.drw-item-sub{font-size:.73rem;color:#94a3b8;margin-top:.1rem}
.drw-remove{width:1.9rem;height:1.9rem;flex-shrink:0;display:flex;align-items:center;justify-content:center;border-radius:.45rem;border:1.5px solid #fecaca;background:#fff;color:#ef4444;font-size:.85rem;cursor:pointer;transition:all .15s}
.drw-remove:hover{background:#fee2e2;border-color:#f87171;transform:scale(1.1)}
/* COL DROITE */
.drw-col-right{display:flex;flex-direction:column;background:#f8fafc;overflow:hidden}
.drw-search-wrap{padding:.75rem 1.1rem;border-bottom:1px solid #f1f5f9;flex-shrink:0}
.drw-search-wrapper{position:relative}
.drw-search-icon{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.9rem;pointer-events:none}
.drw-search-input{width:100%;padding:.62rem 1rem .62rem 2.4rem;border:1.5px solid #e2e8f0;border-radius:.85rem;background:#fff;font-size:.875rem;color:#1e293b;outline:none;transition:border-color .18s,box-shadow .18s;box-sizing:border-box}
.drw-search-input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.drw-results{overflow-y:auto;flex:1;padding:.65rem;display:flex;flex-direction:column;gap:.35rem}
/* RÉSULTATS */
.drw-result-item{display:flex;align-items:center;gap:.65rem;padding:.65rem .85rem;background:#fff;border:1.5px solid #e2e8f0;border-radius:.85rem;cursor:pointer;transition:all .18s}
.drw-result-item:hover:not(.already){border-color:#93c5fd;background:#eff6ff}
.drw-result-item.already{opacity:.55;cursor:default}
.drw-result-info{flex:1;min-width:0}
.drw-result-name{font-size:.85rem;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.drw-result-sub{font-size:.73rem;color:#94a3b8;margin-top:.1rem}
.drw-add-badge{flex-shrink:0;font-size:.73rem;font-weight:700;padding:.22rem .55rem;border-radius:.4rem;background:#dbeafe;color:#1d4ed8;transition:all .15s}
.drw-result-item:hover:not(.already) .drw-add-badge{background:#2563eb;color:#fff}
.drw-ok-badge{flex-shrink:0;font-size:.73rem;font-weight:700;padding:.22rem .55rem;border-radius:.4rem;background:#dcfce7;color:#166534}
/* FOOTER */
.drw-foot{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;background:#fff;border-top:1px solid #e2e8f0;flex-shrink:0;gap:1rem}
.drw-counter{font-size:.82rem;color:#94a3b8}
.drw-counter strong{color:#1e293b}
/* ÉTAT VIDE */
.drw-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;gap:.45rem;color:#94a3b8;font-size:.83rem;text-align:center;padding:2rem;min-height:120px}
.drw-empty-icon{font-size:1.9rem;opacity:.5}
/* TOAST */
#undoToast{position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%) translateY(80px);background:#1e293b;color:#fff;border-radius:.85rem;padding:.65rem 1.25rem;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:.85rem;box-shadow:0 8px 24px rgba(0,0,0,.25);z-index:70;opacity:0;transition:all .25s cubic-bezier(.4,0,.2,1);pointer-events:none;white-space:nowrap}
#undoToast.show{opacity:1;transform:translateX(-50%) translateY(0);pointer-events:all}
#undoToast button{background:#3b82f6;color:#fff;border:none;border-radius:.45rem;padding:.25rem .75rem;font-size:.8rem;font-weight:700;cursor:pointer}
/* ANIM */
@keyframes slideIn{from{opacity:0;transform:translateX(10px)}to{opacity:1;transform:translateX(0)}}
@keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:600px){.drw-body{grid-template-columns:1fr;grid-template-rows:1fr 1fr}.drw-col-left{border-right:none;border-bottom:1px solid #e2e8f0}}
</style>

<!-- OVERLAY -->
<div id="drawerOverlay" onclick="closeDrawer()"></div>

<!-- DRAWER -->
<div id="drawer">

    <!-- HEADER -->
    <div class="drw-head">
        <div class="drw-title">🔧 Modifier la liste de maintenance</div>
        <div style="display:flex;align-items:center;gap:.65rem">
            <button onclick="submitMaintenance()" class="btn-edit">💾 Sauvegarder</button>
            <button class="drw-close" onclick="closeDrawer()" title="Fermer (Échap)">×</button>
        </div>
    </div>

    <!-- CORPS -->
    <div class="drw-body">

        <!-- COL GAUCHE : liste active -->
        <div class="drw-col-left">
            <div class="drw-col-head">
                <span>En maintenance</span>
                <span id="listCount" style="background:#fee2e2;color:#b91c1c;font-size:.7rem;padding:.12rem .5rem;border-radius:9999px;font-weight:800"><?= count($current) ?></span>
            </div>
            <div class="drw-list" id="maintenanceList">
                <?php if (empty($current)): ?>
                    <div class="drw-empty" id="emptyState">
                        <div class="drw-empty-icon">✅</div>
                        <div>Aucun parcours en maintenance</div>
                        <div style="font-size:.75rem">Utilisez la recherche à droite</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($current as $i => $p): ?>
                        <div class="drw-item" data-id="<?= (int)$p['id'] ?>"
                             style="animation:slideIn .2s ease <?= $i * 0.04 ?>s both">
                            <div class="drw-item-num"><?= $i + 1 ?></div>
                            <div class="drw-item-info">
                                <div class="drw-item-name" title="<?= htmlspecialchars($p['titre']) ?>"><?= htmlspecialchars($p['titre']) ?></div>
                                <div class="drw-item-sub">📍 <?= htmlspecialchars($p['ville']) ?><?php if (!empty($p['departement_code'])): ?> (<?= htmlspecialchars($p['departement_code']) ?>)<?php endif; ?></div>
                            </div>
                            <button class="drw-remove" onclick="removeItem(<?= (int)$p['id'] ?>, this)" title="Retirer">✕</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- COL DROITE : recherche -->
        <div class="drw-col-right">
            <div class="drw-col-head">
                <span>Ajouter un parcours</span>
            </div>
            <div class="drw-search-wrap">
                <div class="drw-search-wrapper">
                    <span class="drw-search-icon">🔍</span>
                    <input type="text" id="searchInput" class="drw-search-input"
                           placeholder="Rechercher par nom, ville…" autocomplete="off">
                </div>
            </div>
            <div class="drw-results" id="searchResults">
                <div class="drw-empty">
                    <div class="drw-empty-icon">🗺️</div>
                    <div>Tapez pour rechercher un parcours</div>
                </div>
            </div>
        </div>

    </div><!-- fin drw-body -->

    <!-- FOOTER -->
    <div class="drw-foot">
        <div class="drw-counter"><strong id="footerCount"><?= count($current) ?></strong> parcours en maintenance</div>
        <div style="display:flex;gap:.65rem">
            <button onclick="closeDrawer()" class="btn-history">Annuler</button>
            <button onclick="submitMaintenance()" class="btn-edit">💾 Sauvegarder</button>
        </div>
    </div>

</div>

<!-- TOAST UNDO -->
<div id="undoToast">
    <span id="undoText">Parcours retiré</span>
    <button onclick="undoRemove()">↩ Annuler</button>
</div>

<script>
let maintenanceIds  = <?= json_encode(array_column($current, 'id')) ?>;
let maintenanceData = <?= json_encode(array_values($current)) ?>;
let searchTimeout   = null;
let undoStack       = [];
let undoTimer       = null;
const csrfToken = <?= json_encode($csrfToken) ?>;
let currentVersion = <?= json_encode($currentVersion) ?>;

/* ══ OPEN / CLOSE ══ */
function openModal() {
    document.getElementById('drawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('searchInput').focus(), 300);
}
function closeDrawer() {
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

/* ══ RETIRER ══ */
function removeItem(id, btn) {
    const el   = btn.closest('.drw-item');
    const pos  = maintenanceIds.indexOf(id);
    const data = maintenanceData.find(p => parseInt(p.id) === id);

    maintenanceIds  = maintenanceIds.filter(i => i !== id);
    maintenanceData = maintenanceData.filter(p => parseInt(p.id) !== id);

    el.style.transition = 'all .22s ease';
    el.style.opacity = '0'; el.style.transform = 'translateX(12px)';
    el.style.maxHeight = el.offsetHeight + 'px';
    setTimeout(() => { el.style.maxHeight = '0'; el.style.marginBottom = '0'; el.style.padding = '0'; }, 150);
    setTimeout(() => { el.remove(); renumber(); updateCounter(); checkEmpty(); }, 370);

    markResult(id, false);
    undoStack.push({ id, data, position: pos });
    showUndo(data?.titre ?? 'Parcours');
}

function renumber() {
    document.querySelectorAll('#maintenanceList .drw-item').forEach((el, i) => {
        const n = el.querySelector('.drw-item-num'); if (n) n.textContent = i + 1;
    });
}
function updateCounter() {
    const n = maintenanceIds.length;
    document.getElementById('listCount').textContent = n;
    document.getElementById('footerCount').textContent = n;
}
function checkEmpty() {
    const list = document.getElementById('maintenanceList');
    if (!maintenanceIds.length && !list.querySelector('.drw-empty')) {
        list.innerHTML = '<div class="drw-empty" id="emptyState"><div class="drw-empty-icon">✅</div><div>Aucun parcours en maintenance</div><div style="font-size:.75rem">Utilisez la recherche à droite</div></div>';
    }
}

/* ══ UNDO ══ */
function showUndo(nom) {
    clearTimeout(undoTimer);
    document.getElementById('undoText').textContent = `« ${nom} » retiré`;
    document.getElementById('undoToast').classList.add('show');
    undoTimer = setTimeout(() => { document.getElementById('undoToast').classList.remove('show'); }, 5000);
}
function undoRemove() {
    const last = undoStack.pop(); if (!last) return;
    document.getElementById('undoToast').classList.remove('show');
    maintenanceIds.splice(last.position, 0, last.id);
    maintenanceData.splice(last.position, 0, last.data);
    document.getElementById('emptyState')?.remove();

    const list = document.getElementById('maintenanceList');
    const item = document.createElement('div');
    item.className = 'drw-item'; item.dataset.id = last.id;
    item.style.cssText = 'opacity:0;transform:translateX(-8px);transition:all .25s ease;background:#dbeafe;border-color:#93c5fd';
    item.innerHTML = `
        <div class="drw-item-num">?</div>
        <div class="drw-item-info">
            <div class="drw-item-name">${escHtml(last.data?.titre ?? '')}</div>
            <div class="drw-item-sub">📍 ${escHtml(last.data?.ville ?? '')} ${last.data?.departement_code ? '('+escHtml(last.data.departement_code)+')' : ''}</div>
        </div>
        <button class="drw-remove" title="Retirer">✕</button>`;
    item.querySelector('button').onclick = () => removeItem(last.id, item.querySelector('button'));
    const items = list.querySelectorAll('.drw-item');
    items[last.position] ? list.insertBefore(item, items[last.position]) : list.appendChild(item);
    requestAnimationFrame(() => { item.style.opacity = '1'; item.style.transform = 'translateX(0)'; });
    setTimeout(() => { item.style.background = ''; item.style.borderColor = ''; }, 1500);
    renumber(); updateCounter();
    markResult(last.id, true);
}

/* ══ AJOUTER ══ */
function addItem(p) {
    const id = parseInt(p.id);
    if (maintenanceIds.includes(id)) return;
    maintenanceIds.push(id); maintenanceData.push(p);
    document.getElementById('emptyState')?.remove();

    const list = document.getElementById('maintenanceList');
    const item = document.createElement('div');
    item.className = 'drw-item'; item.dataset.id = id;
    item.style.cssText = 'opacity:0;transform:translateY(6px);transition:all .25s ease;background:#dcfce7;border-color:#86efac';
    item.innerHTML = `
        <div class="drw-item-num">${maintenanceIds.length}</div>
        <div class="drw-item-info">
            <div class="drw-item-name" title="${escHtml(p.titre)}">${escHtml(p.titre)}</div>
            <div class="drw-item-sub">📍 ${escHtml(p.ville)} ${p.departement_code ? '('+escHtml(p.departement_code)+')' : ''}</div>
        </div>
        <button class="drw-remove" title="Retirer">✕</button>`;
    item.querySelector('button').onclick = () => removeItem(id, item.querySelector('button'));
    list.appendChild(item);
    requestAnimationFrame(() => { item.style.opacity = '1'; item.style.transform = 'translateY(0)'; });
    setTimeout(() => { item.style.background = ''; item.style.borderColor = ''; }, 1500);
    updateCounter(); markResult(id, true);
}

/* ══ BADGE RÉSULTAT ══ */
function markResult(id, added) {
    const el = document.querySelector(`#searchResults [data-result-id="${id}"]`);
    if (!el) return;
    if (added) {
        el.classList.add('already'); el.onclick = null;
        const b = el.querySelector('.drw-add-badge');
        if (b) { b.className = 'drw-ok-badge'; b.textContent = '✓ Ajouté'; }
    } else {
        el.classList.remove('already');
        const b = el.querySelector('.drw-ok-badge');
        if (b) { b.className = 'drw-add-badge'; b.textContent = '+ Ajouter'; }
        const d = JSON.parse(el.dataset.parcours || '{}');
        el.onclick = () => addItem(d);
    }
}

/* ══ RECHERCHE ══ */
document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.trim(); clearTimeout(searchTimeout);
    const c = document.getElementById('searchResults');
    if (q.length < 2) {
        c.innerHTML = '<div class="drw-empty"><div class="drw-empty-icon">🗺️</div><div>Tapez pour rechercher un parcours</div></div>';
        return;
    }
    c.innerHTML = '<div class="drw-empty"><div style="font-size:1.5rem">⏳</div><div>Recherche…</div></div>';
    searchTimeout = setTimeout(() => {
        fetch('/parcours/search?q=' + encodeURIComponent(q))
            .then(r => {
                if (!r.ok) {
                    // Lire le body même en cas d'erreur HTTP
                    return r.text().then(text => {
                        let msg = `Erreur serveur (HTTP ${r.status})`;
                        try {
                            const json = JSON.parse(text);
                            if (json.message) msg = json.message;
                        } catch(e) {
                            // Réponse HTML (erreur PHP) — extraire le message si possible
                            const m = text.match(/<b>([^<]{5,200})<\/b>/);
                            if (m) msg = m[1];
                        }
                        throw new Error(msg);
                    });
                }
                return r.json();
            })
            .then(json => {
                if (!json.success) throw new Error(json.message ?? 'Réponse invalide');
                const res = Array.isArray(json) ? json : (json.data ?? []);
                if (!res.length) {
                    c.innerHTML = `<div class="drw-empty"><div class="drw-empty-icon">🔍</div><div>Aucun résultat pour « ${escHtml(q)} »</div></div>`;
                    return;
                }
                c.innerHTML = '';
                res.forEach((p, i) => {
                    const already = maintenanceIds.includes(parseInt(p.id));
                    const item = document.createElement('div');
                    item.className = 'drw-result-item' + (already ? ' already' : '');
                    item.dataset.resultId = p.id;
                    item.dataset.parcours = JSON.stringify(p);
                    item.style.animation = `fadeIn .18s ease ${i * .04}s both`;
                    item.innerHTML = `
                        <div class="drw-result-info">
                            <div class="drw-result-name" title="${escHtml(p.titre)}">${escHtml(p.titre)}</div>
                            <div class="drw-result-sub">📍 ${escHtml(p.ville)} ${p.departement_code ? '('+escHtml(p.departement_code)+')' : ''} ${p.poiz_nom ? '· '+escHtml(p.poiz_nom) : ''}</div>
                        </div>
                        ${already ? '<span class="drw-ok-badge">✓ Ajouté</span>' : '<span class="drw-add-badge">+ Ajouter</span>'}`;
                    if (!already) item.onclick = () => addItem(p);
                    c.appendChild(item);
                });
            })
            .catch(err => {
                console.error('Erreur recherche:', err);
                c.innerHTML = `<div class="drw-empty" style="color:#dc2626">
                    <div class="drw-empty-icon">❌</div>
                    <div style="font-weight:700">Erreur lors de la recherche</div>
                    <div style="font-size:.75rem;margin-top:.3rem;color:#b91c1c;max-width:280px;text-align:center">${escHtml(err.message)}</div>
                </div>`;
            });
    }, 300);
});

/* ══ SAUVEGARDER (AJAX — pas de rechargement complet) ══ */
function submitMaintenance() {
    const saveBtn = document.querySelector('.drw-foot .btn-edit');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = '⏳ Sauvegarde…'; }

    const body = new URLSearchParams();
    maintenanceIds.forEach(id => body.append('parcours[]', id));
    body.append('csrf_token', csrfToken);
    body.append('version', currentVersion);

    fetch('/maintenance/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: body.toString()
    })
    .then(r => r.json())
    .then(json => {
        if (!json.success) {
            showDrawerToast('❌ ' + (json.message ?? 'Erreur'), 'error');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = '💾 Sauvegarder'; }
            return;
        }
        // Mettre à jour la version locale
        if (json.newVersion) currentVersion = json.newVersion;

        closeDrawer();
        showDrawerToast('✅ Liste mise à jour', 'success');

        // Mettre à jour le sous-titre "Dernière modification"
        if (json.updatedAt) {
            const sub = document.querySelector('.maint-sub');
            if (sub) {
                const dateStr = formatDateTimeFR(json.updatedAt);
                const user    = json.username ? ` par <strong>${escHtml(json.username)}</strong>` : '';
                sub.innerHTML = `✏️ Dernière modification le <strong>${dateStr}</strong>${user}`;
            }
        }

        // Rafraîchir les cartes de l'index sans rechargement
        if (typeof loadParcours === 'function') loadParcours(1);

        // Mettre à jour le badge du header
        const badge = document.getElementById('countBadge');
        if (badge) badge.textContent = maintenanceIds.length + ' actif(s)';
    })
    .catch(() => {
        showDrawerToast('❌ Erreur réseau', 'error');
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = '💾 Sauvegarder'; }
    });
}

/* ══ TOAST INLINE (hors drawer) ══ */
function showDrawerToast(msg, type) {
    let t = document.getElementById('drawerStatusToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'drawerStatusToast';
        t.style.cssText = 'position:fixed;top:1.25rem;right:1.25rem;z-index:80;padding:.65rem 1.25rem;border-radius:.85rem;font-size:.875rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.18);opacity:0;transition:opacity .25s ease;pointer-events:none';
        document.body.appendChild(t);
    }
    const colors = { success: { bg:'#dcfce7', color:'#166534' }, error: { bg:'#fee2e2', color:'#991b1b' } };
    const c = colors[type] ?? colors.success;
    t.style.background = c.bg; t.style.color = c.color;
    t.textContent = msg;
    t.style.opacity = '1';
    setTimeout(() => { t.style.opacity = '0'; }, 3000);
}

/* ══ UTILS ══ */
function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatDateTimeFR(str) {
    if (!str) return '—';
    const d = new Date(str);
    if (isNaN(d)) return str;
    const date = d.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    const time = d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
    return date.charAt(0).toUpperCase() + date.slice(1) + ' à ' + time;
}
</script>
