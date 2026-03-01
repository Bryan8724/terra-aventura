<?php
$isAdmin = (($_SESSION['user']['role'] ?? '') === 'admin');
$quetes  = is_array($quetes ?? null) ? $quetes : [];

// ── Pré-calcul global (PHP = 0 JS overhead) ──────────────────────────────────
$stats = ['total_quetes' => count($quetes), 'completes' => 0, 'objets_total' => 0, 'objets_done' => 0];

foreach ($quetes as &$q) {
    $qObjTotal = count($q['objets'] ?? []);
    $qObjDone  = 0;
    foreach ($q['objets'] as &$o) {
        $total   = count($o['parcours'] ?? []);
        $done    = array_filter($o['parcours'] ?? [], fn($p) => !empty($p['obtenu']));
        $o['_total']   = $total;
        $o['_done']    = count($done);
        $o['_isDone']  = ($total > 0 && count($done) === $total);
        $o['_pct']     = $total > 0 ? round((count($done) / $total) * 100) : 0;
        if ($o['_isDone']) $qObjDone++;
    }
    unset($o);
    $q['_obj_total'] = $qObjTotal;
    $q['_obj_done']  = $qObjDone;
    $q['_isDone']    = ($qObjTotal > 0 && $qObjDone === $qObjTotal);
    $q['_pct']       = $qObjTotal > 0 ? round(($qObjDone / $qObjTotal) * 100) : 0;

    $stats['objets_total'] += $qObjTotal;
    $stats['objets_done']  += $qObjDone;
    if ($q['_isDone']) $stats['completes']++;
}
unset($q);

$globalPct = $stats['objets_total'] > 0
    ? round(($stats['objets_done'] / $stats['objets_total']) * 100)
    : 0;
?>

<style>
/* ─── Design tokens ────────────────────────────── */
:root {
    --q-gold:   #f59e0b;
    --q-gold2:  #fbbf24;
    --q-indigo: #4f46e5;
    --q-green:  #16a34a;
    --q-card:   #ffffff;
    --q-border: #e2e8f0;
    --q-muted:  #94a3b8;
}

/* ─── Quête card ───────────────────────────────── */
.quete-card {
    background: var(--q-card);
    border: 1.5px solid var(--q-border);
    border-radius: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    transition: box-shadow .2s;
    overflow: hidden;
}
.quete-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.09); }
.quete-card.is-done { border-color: #bbf7d0; }
.quete-card.is-done .quete-header { background: linear-gradient(135deg,#f0fdf4,#dcfce7); }

/* ─── Header quête ─────────────────────────────── */
.quete-header {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1.5px solid var(--q-border);
    display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}

/* ─── Progress bar ─────────────────────────────── */
.prog-track {
    height: 6px; border-radius: 99px;
    background: #e2e8f0; overflow: hidden; flex: 1; min-width: 80px;
}
.prog-fill {
    height: 100%; border-radius: 99px;
    transition: width .6s cubic-bezier(.4,0,.2,1);
    background: linear-gradient(90deg, var(--q-indigo), #818cf8);
}
.prog-fill.done { background: linear-gradient(90deg, #16a34a, #4ade80); }

/* ─── Objet accordion ──────────────────────────── */
.objet-panel {
    border: 1.5px solid var(--q-border);
    border-radius: 1rem; overflow: hidden;
    transition: box-shadow .15s;
}
.objet-panel.is-done { border-color: #bbf7d0; }
.objet-panel:hover { box-shadow: 0 2px 10px rgba(0,0,0,.07); }

.objet-btn {
    width: 100%; text-align: left;
    padding: .875rem 1.125rem;
    display: flex; align-items: center; gap: .75rem;
    cursor: pointer; background: #fafafa;
    transition: background .15s;
}
.objet-btn:hover { background: #f1f5f9; }
.objet-btn.is-done { background: #f0fdf4; }

.objet-body {
    max-height: 0; overflow: hidden;
    transition: max-height .35s cubic-bezier(.4,0,.2,1),
                opacity .25s ease;
    opacity: 0;
}
.objet-body.open { opacity: 1; }

/* ─── Chevron ──────────────────────────────────── */
.chevron {
    width: 22px; height: 22px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%; background: #e2e8f0;
    transition: transform .25s, background .15s;
    font-size: .65rem; color: #64748b;
}
.objet-btn[aria-expanded="true"] .chevron {
    transform: rotate(90deg); background: var(--q-indigo); color: #fff;
}

/* ─── Parcours cards ───────────────────────────── */
.parcours-grid { padding: 1rem; display: grid; gap: .625rem;
    grid-template-columns: repeat(auto-fill, minmax(210px,1fr)); }

.parcours-item {
    display: flex; align-items: center; gap: .625rem;
    padding: .625rem .75rem;
    border-radius: .75rem;
    border: 1.5px solid #e2e8f0; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.parcours-item:hover { border-color: #c7d2fe; box-shadow: 0 2px 8px rgba(79,70,229,.08); }
.parcours-item.done { border-color: #bbf7d0; background: #f0fdf4; }

.parcours-logo {
    width: 2.25rem; height: 2.25rem; flex-shrink: 0;
    object-fit: contain; border-radius: .375rem;
}
.parcours-logo-placeholder {
    width: 2.25rem; height: 2.25rem; flex-shrink: 0;
    border-radius: .375rem; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    font-size: .875rem; color: #94a3b8;
}

/* ─── Badge ────────────────────────────────────── */
.badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .2rem .65rem; border-radius: 99px;
    font-size: .72rem; font-weight: 600; white-space: nowrap;
}
.badge-done   { background: #dcfce7; color: #15803d; }
.badge-wip    { background: #fef9c3; color: #a16207; }
.badge-season { background: #e0e7ff; color: #3730a3; }
.badge-count  { background: #f1f5f9; color: #475569; }

/* ─── Stat cards ───────────────────────────────── */
.stat-card {
    background: #fff; border-radius: 1rem;
    border: 1.5px solid #e2e8f0;
    padding: 1rem 1.25rem;
    display: flex; align-items: center; gap: .875rem;
}
.stat-icon {
    width: 2.5rem; height: 2.5rem; border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}

/* ─── Animations ───────────────────────────────── */
@keyframes slideDown {
    from { opacity:0; transform:translateY(-6px); }
    to   { opacity:1; transform:translateY(0); }
}
.quete-card { animation: slideDown .28s ease both; }
<?php foreach (array_keys($quetes) as $i): ?>
.quete-card:nth-child(<?= $i+1 ?>) { animation-delay: <?= $i * 40 ?>ms; }
<?php endforeach; ?>
</style>

<!-- ══════════════════════════════════════════════════════════
     EN-TÊTE + STATS
═══════════════════════════════════════════════════════════ -->
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            🎯 Quêtes
        </h1>
        <p class="text-sm text-slate-400 mt-0.5">Suivi de vos quêtes et objets obtenus</p>
    </div>

    <?php if ($isAdmin): ?>
    <a href="/admin/quetes/create"
       class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700 transition text-sm font-semibold shadow-sm">
        ➕ Ajouter une quête
    </a>
    <?php endif; ?>
</div>

<?php if (!$isAdmin && $stats['objets_total'] > 0): ?>
<!-- ── Bandeau progression globale ── -->
<div class="bg-white border border-slate-200 rounded-2xl p-5 mb-6 shadow-sm">
    <div class="flex flex-wrap gap-5 mb-4">

        <div class="stat-card flex-1 min-w-[130px]">
            <div class="stat-icon bg-indigo-50">🎯</div>
            <div>
                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Quêtes</p>
                <p class="text-xl font-bold text-slate-800">
                    <?= $stats['completes'] ?><span class="text-sm text-slate-400 font-normal"> / <?= $stats['total_quetes'] ?></span>
                </p>
            </div>
        </div>

        <div class="stat-card flex-1 min-w-[130px]">
            <div class="stat-icon bg-amber-50">🎒</div>
            <div>
                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Objets</p>
                <p class="text-xl font-bold text-slate-800">
                    <?= $stats['objets_done'] ?><span class="text-sm text-slate-400 font-normal"> / <?= $stats['objets_total'] ?></span>
                </p>
            </div>
        </div>

        <div class="stat-card flex-1 min-w-[130px]">
            <div class="stat-icon <?= $globalPct === 100 ? 'bg-green-50' : 'bg-blue-50' ?>">
                <?= $globalPct === 100 ? '🏆' : '📈' ?>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Progression</p>
                <p class="text-xl font-bold <?= $globalPct === 100 ? 'text-green-600' : 'text-slate-800' ?>">
                    <?= $globalPct ?>%
                </p>
            </div>
        </div>
    </div>

    <div class="space-y-1">
        <div class="flex justify-between text-xs text-slate-400">
            <span>Progression globale</span>
            <span><?= $globalPct ?>%</span>
        </div>
        <div class="prog-track" style="height:8px">
            <div class="prog-fill <?= $globalPct === 100 ? 'done' : '' ?>"
                 style="width:<?= $globalPct ?>%"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════
     LISTE DES QUÊTES
═══════════════════════════════════════════════════════════ -->
<?php if (empty($quetes)): ?>
<div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center">
    <p class="text-4xl mb-3">🗺️</p>
    <p class="text-slate-500 font-medium">Aucune quête disponible pour le moment.</p>
</div>

<?php else: ?>
<div class="space-y-4">

<?php foreach ($quetes as $quete):
    if (empty($quete['id'])) continue;
    $qid = (int)$quete['id'];
?>

<div class="quete-card <?= $quete['_isDone'] ? 'is-done' : '' ?>" id="quete-<?= $qid ?>">

    <!-- ── Header quête ── -->
    <div class="quete-header">

        <!-- Icône statut -->
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base flex-shrink-0
            <?= $quete['_isDone']
                ? 'bg-green-100 text-green-600'
                : 'bg-indigo-100 text-indigo-600' ?>">
            <?= $quete['_isDone'] ? '🏆' : '🎯' ?>
        </div>

        <!-- Nom + saison -->
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-base font-bold text-slate-800 truncate">
                    <?= htmlspecialchars((string)($quete['nom'] ?? '')) ?>
                </h2>
                <?php if (!empty($quete['saison'])): ?>
                <span class="badge badge-season">
                    🗓 Saison <?= htmlspecialchars((string)$quete['saison']) ?>
                </span>
                <?php endif; ?>
                <?php if ($quete['_isDone']): ?>
                <span class="badge badge-done">✔ Complète</span>
                <?php endif; ?>
            </div>

            <?php if (!$isAdmin && $quete['_obj_total'] > 0): ?>
            <div class="flex items-center gap-2 mt-1.5">
                <div class="prog-track">
                    <div class="prog-fill <?= $quete['_isDone'] ? 'done' : '' ?>"
                         style="width:<?= $quete['_pct'] ?>%"></div>
                </div>
                <span class="text-xs text-slate-400 whitespace-nowrap">
                    <?= $quete['_obj_done'] ?>/<?= $quete['_obj_total'] ?> objet<?= $quete['_obj_total'] > 1 ? 's' : '' ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions admin -->
        <?php if ($isAdmin): ?>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="/admin/quetes/edit?id=<?= $qid ?>"
               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium
                      bg-white border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600 transition">
                ✏️ Éditer
            </a>
            <button type="button"
                    data-delete-quete="<?= $qid ?>"
                    data-quete-nom="<?= htmlspecialchars((string)$quete['nom'], ENT_QUOTES) ?>"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium
                           bg-white border border-slate-200 text-slate-600 hover:border-red-300 hover:text-red-600 transition">
                🗑 Supprimer
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Corps : objets ── -->
    <div class="p-4 space-y-2.5">

    <?php foreach (($quete['objets'] ?? []) as $objet):
        $oid = (int)$objet['id'];
    ?>

    <div class="objet-panel <?= $objet['_isDone'] ? 'is-done' : '' ?>">

        <!-- Bouton toggle objet -->
        <button type="button"
                class="objet-btn <?= $objet['_isDone'] ? 'is-done' : '' ?>"
                aria-expanded="false"
                aria-controls="body-<?= $oid ?>"
                onclick="toggleObjet(<?= $oid ?>)">

            <!-- Chevron -->
            <span class="chevron">▶</span>

            <!-- Nom objet -->
            <span class="flex-1 text-sm font-semibold text-slate-700 truncate">
                🎒 <?= htmlspecialchars((string)($objet['nom'] ?? '')) ?>
            </span>

            <!-- Badges statut + compteur -->
            <span class="badge <?= $objet['_isDone'] ? 'badge-done' : 'badge-wip' ?> mr-1">
                <?= $objet['_isDone'] ? '✔ Obtenu' : '⏳ En cours' ?>
            </span>
            <span class="badge badge-count">
                <?= $objet['_done'] ?>/<?= $objet['_total'] ?> parcours
            </span>

            <?php if ($objet['_total'] > 0): ?>
            <!-- Mini barre inline -->
            <div class="hidden sm:block w-16 ml-1">
                <div class="prog-track">
                    <div class="prog-fill <?= $objet['_isDone'] ? 'done' : '' ?>"
                         style="width:<?= $objet['_pct'] ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
        </button>

        <!-- Corps parcours (accordéon) -->
        <div id="body-<?= $oid ?>" class="objet-body">
            <div class="parcours-grid">

            <?php foreach (($objet['parcours'] ?? []) as $p): ?>
            <div class="parcours-item <?= !empty($p['obtenu']) ? 'done' : '' ?>">

                <?php if (!empty($p['logo'])): ?>
                    <img src="<?= htmlspecialchars((string)$p['logo']) ?>"
                         alt=""
                         loading="lazy"
                         class="parcours-logo">
                <?php else: ?>
                    <div class="parcours-logo-placeholder">📍</div>
                <?php endif; ?>

                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-slate-700 truncate flex items-center gap-1">
                        <?= htmlspecialchars((string)($p['nom'] ?? '')) ?>
                        <?php if (!empty($p['obtenu'])): ?>
                            <span class="text-green-500 flex-shrink-0">✔</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-slate-400 truncate">
                        <?= htmlspecialchars((string)($p['ville'] ?? '')) ?>
                        <?php if (!empty($p['dep'])): ?>
                            <span class="opacity-60">(<?= htmlspecialchars((string)$p['dep']) ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            </div>
        </div>
    </div>

    <?php endforeach; ?>

    </div>
</div>

<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
// ─── Accordéon objet ──────────────────────────────────────────────────────────
// Utilise scrollHeight pour une animation sans max-height fixe arbitraire
function toggleObjet(id) {
    const btn  = document.querySelector('[aria-controls="body-' + id + '"]');
    const body = document.getElementById('body-' + id);
    if (!btn || !body) return;

    const isOpen = btn.getAttribute('aria-expanded') === 'true';

    if (isOpen) {
        // Fermer
        body.style.maxHeight = body.scrollHeight + 'px';
        requestAnimationFrame(() => {
            body.style.maxHeight = '0';
            body.style.opacity   = '0';
        });
        btn.setAttribute('aria-expanded', 'false');
        body.classList.remove('open');
    } else {
        // Ouvrir
        body.style.maxHeight = body.scrollHeight + 'px';
        body.style.opacity   = '1';
        btn.setAttribute('aria-expanded', 'true');
        body.classList.add('open');

        // Nettoyer max-height après transition pour s'adapter au redimensionnement
        body.addEventListener('transitionend', () => {
            if (btn.getAttribute('aria-expanded') === 'true') {
                body.style.maxHeight = 'none';
            }
        }, { once: true });
    }
}

// ─── Suppression quête (admin) ────────────────────────────────────────────────
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-delete-quete]');
    if (!btn) return;

    const id  = btn.dataset.deleteQuete;
    const nom = btn.dataset.queteNom;

    const ok = await taConfirm(
        'Supprimer la quête "' + nom + '" ?',
        { sub: 'Cette action est irréversible.', icon: '🗑️', okLabel: 'Supprimer', okColor: '#dc2626' }
    );
    if (!ok) return;

    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/admin/quetes/delete';

    const inp = document.createElement('input');
    inp.type  = 'hidden';
    inp.name  = 'id';
    inp.value = id;
    form.appendChild(inp);

    // CSRF token
    const csrf = document.querySelector('input[name="csrf_token"]');
    if (csrf) {
        const c = document.createElement('input');
        c.type  = 'hidden';
        c.name  = 'csrf_token';
        c.value = csrf.value;
        form.appendChild(c);
    }

    document.body.appendChild(form);
    form.submit();
});
</script>
