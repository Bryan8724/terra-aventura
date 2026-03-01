<?php
$user       = $_SESSION['user'] ?? null;
$isAdmin    = ($user['role'] ?? '') === 'admin';
$meta       = $meta ?? null;
$parcours   = $parcours ?? [];
$csrfToken  = $_SESSION['csrf_token'] ?? '';

$lastUpdate = null;
if (!empty($meta['updated_at'])) {
    try {
        $dt = new DateTime($meta['updated_at'], new DateTimeZone('Europe/Paris'));
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');
        $jours   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        $mois    = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        $lastUpdate = $jours[(int)$dt->format('w')] . ' ' . (int)$dt->format('j') . ' ' . $mois[(int)$dt->format('n')] . ' ' . $dt->format('Y') . ' à ' . $dt->format('H:i');
    } catch (Exception $e) {}
}
$nbParcours = count($parcours);
?>

<style>
.maint-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:2rem;gap:1rem;flex-wrap:wrap}
.maint-title{font-size:1.5rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:.75rem}
.maint-count{font-size:.8rem;font-weight:700;padding:.2rem .75rem;border-radius:9999px;background:#fee2e2;color:#b91c1c}
.maint-sub{font-size:.82rem;color:#94a3b8;margin-top:.35rem}
.maint-actions{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}
.btn-history{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.1rem;border-radius:.75rem;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .18s}
.btn-history:hover{background:#f8fafc;border-color:#cbd5e1;color:#1e293b}
.btn-edit{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.25rem;border-radius:.75rem;background:#2563eb;color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;border:none;transition:all .18s;box-shadow:0 2px 8px rgba(37,99,235,.25)}
.btn-edit:hover{background:#1d4ed8}
.btn-edit-disabled{background:#94a3b8!important;cursor:not-allowed!important;box-shadow:none!important}
.maint-empty{background:#fff;border:2px dashed #e2e8f0;border-radius:1.25rem;padding:3.5rem 2rem;text-align:center}
.maint-card{background:#fff;border:1px solid #fee2e2;border-left:4px solid #ef4444;border-radius:1rem;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1.25rem;transition:box-shadow .18s,transform .18s}
.maint-card:hover{box-shadow:0 4px 20px rgba(239,68,68,.1);transform:translateY(-1px)}
.maint-card-logo{width:3rem;height:3rem;object-fit:contain;border-radius:.5rem;background:#fef2f2;border:1px solid #fecaca;padding:.2rem;flex-shrink:0}
.maint-card-placeholder{width:3rem;height:3rem;flex-shrink:0;border-radius:.5rem;background:#fef2f2;display:flex;align-items:center;justify-content:center;font-size:1.25rem}
.maint-card-title{font-size:.95rem;font-weight:700;color:#991b1b}
.maint-card-sub{font-size:.78rem;color:#94a3b8;margin-top:.2rem}
.btn-detail{display:inline-flex;align-items:center;gap:.3rem;padding:.45rem .9rem;border-radius:.65rem;background:#fef2f2;color:#b91c1c;border:1.5px solid #fecaca;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .18s}
.btn-detail:hover{background:#fee2e2;border-color:#f87171}
.page-btn{padding:.4rem .85rem;border-radius:.5rem;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .18s}
.page-btn:hover{background:#f1f5f9}
.page-btn.active{background:#2563eb;border-color:#2563eb;color:#fff}
.modal-overlay{position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);backdrop-filter:blur(3px)}
.modal-box{background:#fff;border-radius:1.25rem;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;animation:modalIn .2s ease}
@keyframes modalIn{from{opacity:0;transform:scale(.96) translateY(8px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-header{display:flex;justify-content:space-between;align-items:center;padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9}
.modal-header h2{font-size:1.05rem;font-weight:700;color:#1e293b}
.modal-close{width:2rem;height:2rem;display:flex;align-items:center;justify-content:center;border-radius:.5rem;border:none;background:transparent;cursor:pointer;color:#94a3b8;font-size:1.2rem;transition:all .15s;line-height:1}
.modal-close:hover{background:#f1f5f9;color:#475569}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:1.25rem 1.5rem}
.detail-cell{background:#f8fafc;border-radius:.75rem;padding:.85rem 1rem}
.detail-cell.span2{grid-column:span 2}
.detail-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:.25rem}
.detail-value{font-size:.9rem;font-weight:600;color:#1e293b}
.hist-entry{border-radius:.85rem;border:1px solid #f1f5f9;overflow:hidden;transition:box-shadow .18s}
.hist-entry:hover{box-shadow:0 2px 12px rgba(0,0,0,.06)}
.hist-header{display:flex;align-items:center;gap:.75rem;padding:.85rem 1.1rem;background:#f8fafc;cursor:pointer;user-select:none}
.hist-avatar{width:2.2rem;height:2.2rem;border-radius:9999px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0}
.hist-meta{flex:1;min-width:0}
.hist-user{font-size:.85rem;font-weight:700;color:#1e293b}
.hist-date{font-size:.75rem;color:#94a3b8;margin-top:.05rem}
.hist-badges{display:flex;gap:.4rem;margin-left:auto;flex-shrink:0;flex-wrap:wrap}
.hist-badge{font-size:.7rem;font-weight:700;padding:.15rem .55rem;border-radius:9999px}
.hist-badge.added{background:#dcfce7;color:#15803d}
.hist-badge.removed{background:#fee2e2;color:#b91c1c}
.hist-badge.none{background:#f1f5f9;color:#94a3b8}
.hist-body{padding:.85rem 1.1rem;border-top:1px solid #f1f5f9;background:#fff}
.hist-diff-line{display:flex;align-items:center;gap:.5rem;padding:.3rem 0;font-size:.83rem}
.hist-diff-line+.hist-diff-line{border-top:1px solid #f8fafc}
.hist-diff-line .dot{width:.45rem;height:.45rem;border-radius:9999px;flex-shrink:0}
.hist-diff-line .dot.add{background:#22c55e}
.hist-diff-line .dot.rem{background:#ef4444}
.hist-diff-label{padding:.1rem .55rem;border-radius:9999px;font-size:.7rem;font-weight:700;white-space:nowrap}
.hist-diff-label.add{background:#dcfce7;color:#15803d}
.hist-diff-label.rem{background:#fee2e2;color:#b91c1c}
.hist-diff-name{color:#374151}
.hist-empty{text-align:center;padding:2.5rem 1rem;color:#94a3b8;font-size:.85rem}
.edit-item{display:flex;justify-content:space-between;align-items:center;padding:.7rem .9rem;border-radius:.65rem;border:1px solid #e2e8f0;background:#fff;transition:background .15s}
.edit-item:hover{background:#f8fafc}
.edit-item-title{font-size:.85rem;font-weight:600;color:#1e293b}
.edit-item-sub{font-size:.75rem;color:#94a3b8;margin-top:.1rem}
.btn-remove{padding:.3rem .75rem;border-radius:.5rem;background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;font-size:.75rem;font-weight:600;cursor:pointer;transition:all .15s}
.btn-remove:hover{background:#fecaca}
.search-result-item{display:flex;justify-content:space-between;align-items:center;padding:.65rem .9rem;border-radius:.65rem;border:1px solid #e2e8f0;background:#fff;transition:background .15s}
.search-result-item:hover{background:#f8fafc}
.btn-add-item{padding:.3rem .75rem;border-radius:.5rem;background:#dcfce7;color:#15803d;border:1.5px solid #86efac;font-size:.75rem;font-weight:600;cursor:pointer;transition:all .15s}
.btn-add-item:hover{background:#bbf7d0}
@keyframes shake{0%,100%{transform:translateX(0)}25%,75%{transform:translateX(-4px)}50%{transform:translateX(4px)}}
.shake{animation:shake .3s ease}
</style>

<!-- ═══════════ HEADER ═══════════ -->
<div class="maint-header">
    <div>
        <div class="maint-title">
            🛠️ Maintenance des parcours
            <span class="maint-count" id="countBadge"><?= $nbParcours ?> actif(s)</span>
        </div>
        <?php if ($lastUpdate): ?>
            <p class="maint-sub">
                ✏️ Dernière modification le <strong><?= $lastUpdate ?></strong>
                <?php if (!empty($meta['username'])): ?>
                    par <strong><?= htmlspecialchars($meta['username']) ?></strong>
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="maint-sub">Aucune modification enregistrée</p>
        <?php endif; ?>
    </div>

    <div class="maint-actions">
        <button onclick="openHistoryModal()" class="btn-history">🕓 Historique</button>
        <button onclick="openModal()" class="btn-edit">✏️ Éditer la liste</button>
    </div>
</div>

<!-- ═══════════ LISTE PARCOURS ═══════════ -->
<div id="parcoursContainer" class="space-y-3"></div>
<div id="paginationContainer" class="flex justify-center gap-2 mt-6 flex-wrap"></div>

<!-- ═══════════ MODAL HISTORIQUE ═══════════ -->
<div id="historyModal" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:680px;margin:1rem;">
        <div class="modal-header">
            <h2>🕓 Historique des modifications</h2>
            <button onclick="closeHistoryModal()" class="modal-close">×</button>
        </div>
        <div style="padding:1rem 1.5rem 1.5rem">
            <div id="historyContainer" class="space-y-2" style="max-height:460px;overflow-y:auto;padding-right:4px"></div>
            <div id="historyPagination" class="flex justify-center gap-2 mt-4 flex-wrap"></div>
        </div>
    </div>
</div>

<!-- ═══════════ MODAL DÉTAIL PARCOURS ═══════════ -->
<div id="parcoursModal" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:540px;margin:1rem;">
        <div style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:1.25rem 1.5rem;border-radius:1.25rem 1.25rem 0 0;display:flex;align-items:center;justify-content:space-between">
            <h2 id="modalTitre" style="color:#fff;font-weight:700;font-size:1.05rem"></h2>
            <button onclick="closeParcoursModal()" class="modal-close" style="color:rgba(255,255,255,.7)">×</button>
        </div>
        <div id="modalContent" class="detail-grid"></div>
        <div style="padding:.85rem 1.5rem 1.25rem;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end">
            <button onclick="closeParcoursModal()" class="btn-history">Fermer</button>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_modal.php'; ?>

<script>
/* ═══ DATES EN FRANÇAIS ═══ */
function formatDateTimeFR(str) {
    if (!str) return '—';
    const d = new Date(str);
    if (isNaN(d)) return str;
    const date = d.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    const time = d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
    return date.charAt(0).toUpperCase() + date.slice(1) + ' à ' + time;
}

/* ═══ PARCOURS EN MAINTENANCE ═══ */
async function loadParcours(page = 1) {
    const res  = await fetch(`/maintenance/ajaxParcours?page=${page}`);
    const json = await res.json();
    const container = document.getElementById('parcoursContainer');
    container.innerHTML = '';
    if (!json.data || json.data.length === 0) {
        container.innerHTML = `<div class="maint-empty"><div style="font-size:3rem;margin-bottom:.75rem">✅</div><div style="font-size:1rem;font-weight:600;color:#64748b">Aucun parcours en maintenance</div><div style="font-size:.82rem;color:#94a3b8;margin-top:.25rem">Tous les parcours sont disponibles</div></div>`;
        document.getElementById('countBadge').textContent = '0 actif(s)';
        return;
    }
    document.getElementById('countBadge').textContent = json.data.length + ' actif(s)';
    json.data.forEach(p => { container.innerHTML += renderCard(p); });
    renderPagination(json.page, json.totalPages, loadParcours, 'paginationContainer');
}

function renderCard(p) {
    const logo = p.poiz_logo
        ? `<img src="${p.poiz_logo}" class="maint-card-logo">`
        : `<div class="maint-card-placeholder">🔧</div>`;
    const dist = p.distance_km ? ` · 📏 ${parseFloat(p.distance_km).toFixed(1)} km` : '';
    const duree = p.duree ? ` · ⏱ ${p.duree}` : '';
    return `<div class="maint-card">
        <div style="display:flex;align-items:center;gap:1rem">
            ${logo}
            <div>
                <div class="maint-card-title">${p.titre}</div>
                <div class="maint-card-sub">📍 ${p.ville ?? ''} (${p.departement_code ?? ''})${dist}${duree}</div>
            </div>
        </div>
        <button onclick='openParcoursModal(${JSON.stringify(p)})' class="btn-detail">🔍 Détails</button>
    </div>`;
}

/* ═══ HISTORIQUE ═══ */
async function loadHistory(page = 1) {
    const res  = await fetch(`/maintenance/ajaxHistoryDiff?page=${page}`);
    const json = await res.json();
    const container = document.getElementById('historyContainer');
    container.innerHTML = '';
    if (!json.data || json.data.length === 0) {
        container.innerHTML = `<div class="hist-empty">🗂️<br>Aucun historique disponible</div>`;
        return;
    }
    json.data.forEach(h => {
        const nbAdded   = h.added.length;
        const nbRemoved = h.removed.length;
        const initials  = (h.username || '?').substring(0,2).toUpperCase();
        let badges = '';
        if (nbAdded)   badges += `<span class="hist-badge added">+${nbAdded} ajouté${nbAdded>1?'s':''}</span>`;
        if (nbRemoved) badges += `<span class="hist-badge removed">−${nbRemoved} retiré${nbRemoved>1?'s':''}</span>`;
        if (!nbAdded && !nbRemoved) badges = `<span class="hist-badge none">Aucun changement</span>`;
        let diff = '';
        h.added.forEach(n => { diff += `<div class="hist-diff-line"><span class="dot add"></span><span class="hist-diff-label add">Ajouté</span><span class="hist-diff-name">${n}</span></div>`; });
        h.removed.forEach(n => { diff += `<div class="hist-diff-line"><span class="dot rem"></span><span class="hist-diff-label rem">Retiré</span><span class="hist-diff-name">${n}</span></div>`; });
        if (!diff) diff = `<div class="hist-diff-line" style="color:#94a3b8;font-size:.8rem">— Aucune modification de la liste</div>`;
        const eid = 'hist-' + h.id;
        container.innerHTML += `<div class="hist-entry">
            <div class="hist-header" onclick="toggleHistEntry('${eid}')">
                <div class="hist-avatar">${initials}</div>
                <div class="hist-meta">
                    <div class="hist-user">${h.username || 'Inconnu'}</div>
                    <div class="hist-date">${formatDateTimeFR(h.updated_at)}</div>
                </div>
                <div class="hist-badges">${badges}</div>
                <span id="arr-${eid}" style="color:#cbd5e1;margin-left:.5rem;font-size:.8rem">▼</span>
            </div>
            <div id="${eid}" class="hist-body" style="display:none">${diff}</div>
        </div>`;
    });
    renderPagination(json.page, json.totalPages, loadHistory, 'historyPagination');
}

function toggleHistEntry(id) {
    const body = document.getElementById(id);
    const arr  = document.getElementById('arr-' + id);
    const open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    arr.textContent    = open ? '▼' : '▲';
}

/* ═══ PAGINATION ═══ */
function renderPagination(current, total, callback, containerId) {
    const c = document.getElementById(containerId);
    c.innerHTML = '';
    if (total <= 1) return;
    for (let i = 1; i <= total; i++) {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (i === current ? ' active' : '');
        btn.textContent = i;
        btn.onclick = () => callback(i);
        c.appendChild(btn);
    }
}

/* ═══ MODALS ═══ */
function openHistoryModal()  { document.getElementById('historyModal').style.display  = 'flex'; loadHistory(); }
function closeHistoryModal() { document.getElementById('historyModal').style.display  = 'none'; }

function openParcoursModal(data) {
    document.getElementById('parcoursModal').style.display = 'flex';
    document.getElementById('modalTitre').textContent = data.titre ?? '';
    const niveaux  = ['','Facile','Modéré','Intermédiaire','Difficile','Expert'];
    const terrains = ['','Plat','Peu vallonné','Vallonné','Accidenté','Très accidenté'];
    document.getElementById('modalContent').innerHTML = `
        ${data.poiz_logo ? `<div class="detail-cell span2" style="text-align:center;background:linear-gradient(135deg,#fff5f5,#fee2e2)"><img src="${data.poiz_logo}" style="width:4rem;height:4rem;object-fit:contain;margin:0 auto"><div style="font-size:.8rem;font-weight:600;color:#ef4444;margin-top:.35rem">${data.poiz_nom ?? ''}</div></div>` : ''}
        <div class="detail-cell"><div class="detail-label">📍 Ville</div><div class="detail-value">${data.ville ?? '—'}</div></div>
        <div class="detail-cell"><div class="detail-label">🗺️ Département</div><div class="detail-value">${data.departement_code ?? '—'}</div></div>
        <div class="detail-cell"><div class="detail-label">📏 Distance</div><div class="detail-value">${data.distance_km ? parseFloat(data.distance_km).toFixed(1) + ' km' : '—'}</div></div>
        <div class="detail-cell"><div class="detail-label">⏱ Durée</div><div class="detail-value">${data.duree ?? '—'}</div></div>
        <div class="detail-cell"><div class="detail-label">⚡ Niveau</div><div class="detail-value">${niveaux[parseInt(data.niveau)] ?? '—'}</div></div>
        <div class="detail-cell"><div class="detail-label">🏔️ Terrain</div><div class="detail-value">${terrains[parseInt(data.terrain)] ?? '—'}</div></div>`;
}
function closeParcoursModal() { document.getElementById('parcoursModal').style.display = 'none'; }

['historyModal','parcoursModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeHistoryModal(); closeParcoursModal(); } });

loadParcours();
</script>
