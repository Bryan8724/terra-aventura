<?php
$isAdmin = (($_SESSION['user']['role'] ?? '') === 'admin');
$quetes  = is_array($quetes ?? null) ? $quetes : [];

// ── Pré-calcul stats globales ──────────────────────────────────────────────
$stats = [
    'total_quetes'  => count($quetes),
    'completes'     => 0,
    'objets_total'  => 0,
    'objets_done'   => 0,
];
foreach ($quetes as $q) {
    $stats['objets_total'] += (int)($q['objets_total'] ?? 0);
    $stats['objets_done']  += (int)($q['objets_obtenus'] ?? 0);
    if (!empty($q['tous_objets_ok'])) $stats['completes']++;
}
$globalPct = $stats['objets_total'] > 0
    ? round(($stats['objets_done'] / $stats['objets_total']) * 100)
    : 0;
?>

<style>
:root {
    --q-gold:   #f59e0b;
    --q-indigo: #4f46e5;
    --q-green:  #16a34a;
    --q-card:   #ffffff;
    --q-border: #e2e8f0;
    --q-muted:  #94a3b8;
    --q-orange: #ea580c;
}

.quete-card {
    background: var(--q-card);
    border: 1.5px solid var(--q-border);
    border-radius: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    transition: box-shadow .2s;
    overflow: hidden;
    animation: slideDown .28s ease both;
}
.quete-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.09); }
.quete-card.is-done  { border-color: #bbf7d0; }
.quete-card.is-unlock { border-color: #fed7aa; }
.quete-card.is-done  .quete-header { background: linear-gradient(135deg,#f0fdf4,#dcfce7); }
.quete-card.is-unlock .quete-header { background: linear-gradient(135deg,#fff7ed,#ffedd5); }

.quete-header {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-bottom: 1.5px solid var(--q-border);
    display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}

.prog-track {
    height: 6px; border-radius: 99px;
    background: #e2e8f0; overflow: hidden; flex: 1; min-width: 80px;
}
.prog-fill {
    height: 100%; border-radius: 99px;
    transition: width .6s cubic-bezier(.4,0,.2,1);
    background: linear-gradient(90deg, var(--q-indigo), #818cf8);
}
.prog-fill.done   { background: linear-gradient(90deg, #16a34a, #4ade80); }
.prog-fill.unlock { background: linear-gradient(90deg, var(--q-gold), #fbbf24); }

.objet-panel {
    border: 1.5px solid var(--q-border);
    border-radius: 1rem; overflow: hidden;
    transition: box-shadow .15s;
}
.objet-panel.is-done   { border-color: #bbf7d0; }
.objet-panel.can-confirm { border-color: #fde68a; box-shadow: 0 0 0 3px #fef9c360; }

.objet-btn {
    width: 100%; text-align: left;
    padding: .875rem 1.125rem;
    display: flex; align-items: center; gap: .75rem;
    cursor: pointer; background: #fafafa;
    transition: background .15s;
}
.objet-btn:hover   { background: #f1f5f9; }
.objet-btn.is-done { background: #f0fdf4; }
.objet-btn.can-confirm { background: #fffbeb; }

.objet-body {
    max-height: 0; overflow: hidden;
    transition: max-height .35s cubic-bezier(.4,0,.2,1), opacity .25s ease;
    opacity: 0;
}
.objet-body.open { opacity: 1; }

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

.parcours-grid {
    padding: 1rem; display: grid; gap: .625rem;
    grid-template-columns: repeat(auto-fill, minmax(210px,1fr));
}
.parcours-item {
    display: flex; align-items: center; gap: .625rem;
    padding: .625rem .75rem;
    border-radius: .75rem;
    border: 1.5px solid #e2e8f0; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.parcours-item:hover { border-color: #c7d2fe; }
.parcours-item.done  { border-color: #bbf7d0; background: #f0fdf4; }

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

/* ── Bouton "J'ai obtenu cet objet" ── */
.btn-confirm-objet {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .4rem .9rem; border-radius: .6rem;
    background: #f59e0b; color: #fff;
    font-size: .75rem; font-weight: 700;
    border: none; cursor: pointer;
    transition: background .15s, transform .1s;
    white-space: nowrap;
}
.btn-confirm-objet:hover  { background: #d97706; transform: scale(1.03); }
.btn-confirm-objet:active { transform: scale(.97); }

/* ── Section Parcours Final ── */
.final-section {
    margin: 0 1rem 1rem;
    border: 2px solid #fed7aa;
    border-radius: 1rem;
    background: linear-gradient(135deg, #fff7ed, #fffbeb);
    overflow: hidden;
}
.final-header {
    padding: .875rem 1.125rem;
    background: linear-gradient(135deg, #ffedd5, #fef3c7);
    border-bottom: 1.5px solid #fed7aa;
    display: flex; align-items: center; gap: .6rem;
    cursor: pointer;
}
.final-header h3 { font-size: .95rem; font-weight: 700; color: #92400e; }

.pf-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1.125rem;
    border-top: 1px solid #fed7aa;
}
.pf-item.done { background: #f0fdf4; }

.pf-form {
    padding: .875rem 1.125rem;
    border-top: 1px solid #fed7aa;
    background: #fffbeb;
    display: none;
}
.pf-form.open { display: block; }

.badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .2rem .65rem; border-radius: 99px;
    font-size: .72rem; font-weight: 600; white-space: nowrap;
}
.badge-done    { background: #dcfce7; color: #15803d; }
.badge-wip     { background: #fef9c3; color: #a16207; }
.badge-season  { background: #e0e7ff; color: #3730a3; }
.badge-count   { background: #f1f5f9; color: #475569; }
.badge-confirm { background: #fef9c3; color: #a16207; }
.badge-unlock  { background: #ffedd5; color: #c2410c; font-weight: 700; }

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

@keyframes slideDown {
    from { opacity:0; transform:translateY(-6px); }
    to   { opacity:1; transform:translateY(0); }
}
<?php foreach (array_keys($quetes) as $i): ?>
.quete-card:nth-child(<?= $i+1 ?>) { animation-delay: <?= $i * 40 ?>ms; }
<?php endforeach; ?>
</style>

<!-- ══════════ EN-TÊTE ══════════ -->
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">🎯 Quêtes</h1>
        <p class="text-sm text-slate-400 mt-0.5">Suivi de vos quêtes, objets et parcours finaux</p>
    </div>
    <?php if ($isAdmin): ?>
    <a href="/admin/quetes/create"
       class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700 transition text-sm font-semibold shadow-sm">
        ➕ Ajouter une quête
    </a>
    <?php endif; ?>
</div>

<!-- ══════════ STATS GLOBALES ══════════ -->
<?php if (!$isAdmin && $stats['objets_total'] > 0): ?>
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
                <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Objets confirmés</p>
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
                <p class="text-xl font-bold <?= $globalPct === 100 ? 'text-green-600' : 'text-slate-800' ?>"><?= $globalPct ?>%</p>
            </div>
        </div>
    </div>
    <div class="space-y-1">
        <div class="flex justify-between text-xs text-slate-400">
            <span>Progression globale</span><span><?= $globalPct ?>%</span>
        </div>
        <div class="prog-track" style="height:8px">
            <div class="prog-fill <?= $globalPct === 100 ? 'done' : '' ?>" style="width:<?= $globalPct ?>%"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ══════════ LISTE DES QUÊTES ══════════ -->
<?php if (empty($quetes)): ?>
<div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center">
    <p class="text-4xl mb-3">🗺️</p>
    <p class="text-slate-500 font-medium">Aucune quête disponible pour le moment.</p>
</div>

<?php else: ?>
<div class="space-y-4">
<?php foreach ($quetes as $quete):
    if (empty($quete['id'])) continue;
    $qid          = (int)$quete['id'];
    $tousObjOk    = !empty($quete['tous_objets_ok']);
    $parcoursFinaux = $quete['parcours_final'] ?? [];
    $cardClass    = $tousObjOk ? 'is-done' : '';
    // Vérifier s'il y a des objets à confirmer
    $hasConfirmable = false;
    foreach ($quete['objets'] ?? [] as $o) {
        if (!empty($o['peut_confirmer'])) { $hasConfirmable = true; break; }
    }
?>

<div class="quete-card <?= $cardClass ?>" id="quete-<?= $qid ?>">

    <!-- ── Header ── -->
    <div class="quete-header">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base flex-shrink-0
            <?= $tousObjOk ? 'bg-green-100 text-green-600' : ($hasConfirmable ? 'bg-amber-100 text-amber-600' : 'bg-indigo-100 text-indigo-600') ?>">
            <?= $tousObjOk ? '🏆' : ($hasConfirmable ? '🎒' : '🎯') ?>
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-base font-bold text-slate-800 truncate">
                    <?= htmlspecialchars((string)($quete['nom'] ?? '')) ?>
                </h2>
                <?php if (!empty($quete['saison'])): ?>
                <span class="badge badge-season">🗓 Saison <?= htmlspecialchars((string)$quete['saison']) ?></span>
                <?php endif; ?>
                <?php if ($tousObjOk): ?>
                    <span class="badge badge-done">✔ Tous objets obtenus</span>
                <?php elseif ($hasConfirmable): ?>
                    <span class="badge badge-confirm">🎒 Objet(s) à confirmer !</span>
                <?php endif; ?>
            </div>

            <?php if (!$isAdmin): ?>
            <div class="flex items-center gap-2 mt-1.5">
                <div class="prog-track">
                    <div class="prog-fill <?= $tousObjOk ? 'done' : ($hasConfirmable ? 'unlock' : '') ?>"
                         style="width:<?= ($quete['objets_total'] ?? 0) > 0 ? round(($quete['objets_obtenus'] / $quete['objets_total']) * 100) : 0 ?>%"></div>
                </div>
                <span class="text-xs text-slate-400 whitespace-nowrap">
                    <?= (int)($quete['objets_obtenus'] ?? 0) ?>/<?= (int)($quete['objets_total'] ?? 0) ?> objet<?= ($quete['objets_total'] ?? 0) > 1 ? 's' : '' ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($isAdmin): ?>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="/admin/quetes/edit?id=<?= $qid ?>"
               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600 transition">
                ✏️ Éditer
            </a>
            <button type="button" data-delete-quete="<?= $qid ?>"
                    data-quete-nom="<?= htmlspecialchars((string)$quete['nom'], ENT_QUOTES) ?>"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-slate-200 text-slate-600 hover:border-red-300 hover:text-red-600 transition">
                🗑 Supprimer
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Objets ── -->
    <div class="p-4 space-y-2.5">
    <?php foreach (($quete['objets'] ?? []) as $objet):
        $oid          = (int)$objet['id'];
        $obtenu       = !empty($objet['obtenu']);
        $peutConfirmer = !empty($objet['peut_confirmer']);
        $panelClass   = $obtenu ? 'is-done' : ($peutConfirmer ? 'can-confirm' : '');
        $btnClass     = $obtenu ? 'is-done' : ($peutConfirmer ? 'can-confirm' : '');
        $nbParcours   = count($objet['parcours'] ?? []);
        $nbEffectues  = count(array_filter($objet['parcours'] ?? [], fn($p) => !empty($p['effectue'])));
    ?>

    <div class="objet-panel <?= $panelClass ?>" id="panel-<?= $oid ?>">

        <button type="button"
                class="objet-btn <?= $btnClass ?>"
                aria-expanded="false"
                aria-controls="body-<?= $oid ?>"
                onclick="toggleObjet(<?= $oid ?>)">

            <span class="chevron">▶</span>

            <span class="flex-1 text-sm font-semibold text-slate-700 truncate">
                <?= $obtenu ? '✅' : ($peutConfirmer ? '🎒' : '🎒') ?>
                <?= htmlspecialchars((string)($objet['nom'] ?? '')) ?>
            </span>

            <!-- Badge statut -->
            <?php if ($obtenu): ?>
                <span class="badge badge-done mr-1">✔ Confirmé</span>
            <?php elseif ($peutConfirmer): ?>
                <!-- Bouton confirmation explicite -->
                <button type="button"
                        class="btn-confirm-objet"
                        onclick="event.stopPropagation(); confirmerObjet(<?= $oid ?>, '<?= htmlspecialchars((string)$objet['nom'], ENT_QUOTES) ?>', <?= $qid ?>)"
                        title="Vous avez validé un parcours lié à cet objet, confirmez-vous l'avoir obtenu ?">
                    🎒 J'ai obtenu cet objet !
                </button>
            <?php else: ?>
                <span class="badge badge-wip mr-1">⏳ En cours</span>
            <?php endif; ?>

            <span class="badge badge-count"><?= $nbEffectues ?>/<?= $nbParcours ?> parcours</span>

            <?php if ($nbParcours > 0): ?>
            <div class="hidden sm:block w-16 ml-1">
                <div class="prog-track">
                    <div class="prog-fill <?= $obtenu ? 'done' : '' ?>"
                         style="width:<?= $nbParcours > 0 ? round($nbEffectues / $nbParcours * 100) : 0 ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
        </button>

        <!-- Corps parcours -->
        <div id="body-<?= $oid ?>" class="objet-body">
            <div class="parcours-grid">
            <?php foreach (($objet['parcours'] ?? []) as $p): ?>
            <div class="parcours-item <?= !empty($p['effectue']) ? 'done' : '' ?>">
                <?php if (!empty($p['logo'])): ?>
                    <img src="<?= htmlspecialchars((string)$p['logo']) ?>" alt="" loading="lazy" class="parcours-logo">
                <?php else: ?>
                    <div class="parcours-logo-placeholder">📍</div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold text-slate-700 truncate flex items-center gap-1">
                        <?= htmlspecialchars((string)($p['nom'] ?? '')) ?>
                        <?php if (!empty($p['effectue'])): ?>
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

    <!-- ══════════ PARCOURS FINAL (débloqué quand tous les objets OK) ══════════ -->
    <?php if ($tousObjOk && !$isAdmin): ?>
    <div class="final-section" id="final-<?= $qid ?>">

        <!-- Header cliquable -->
        <div class="final-header" onclick="toggleFinal(<?= $qid ?>)">
            <span style="font-size:1.1rem">🏁</span>
            <h3>Parcours Final</h3>
            <?php if (empty($parcoursFinaux)): ?>
                <span class="badge badge-unlock ml-1">🔓 Débloqué ! À renseigner</span>
            <?php else: ?>
                <span class="badge badge-count ml-1"><?= count($parcoursFinaux) ?> parcours</span>
            <?php endif; ?>
            <span class="ml-auto text-amber-700 text-sm" id="final-chevron-<?= $qid ?>">▼</span>
        </div>

        <!-- Corps -->
        <div id="final-body-<?= $qid ?>" style="display:none">

            <?php if (empty($parcoursFinaux)): ?>
            <div class="px-4 py-3 text-sm text-amber-800 bg-amber-50 border-b border-amber-200">
                🎉 Félicitations ! Tous vos objets sont obtenus. Renseignez maintenant le ou les parcours finaux de cette quête.
            </div>
            <?php endif; ?>

            <!-- Liste des parcours finaux -->
            <?php foreach ($parcoursFinaux as $pf): ?>
            <div class="pf-item <?= $pf['date_validation'] ? 'done' : '' ?>">
                <div class="flex-1">
                    <span class="text-sm font-bold text-slate-700"><?= htmlspecialchars($pf['titre']) ?></span>
                    <?php if ($pf['ville']): ?>
                        <span class="text-xs text-slate-400 ml-2"><?= htmlspecialchars($pf['ville']) ?></span>
                    <?php endif; ?>
                    <?php if ($pf['distance_km']): ?>
                        <span class="text-xs text-slate-400 ml-2">• <?= $pf['distance_km'] ?> km</span>
                    <?php endif; ?>
                    <?php if ($pf['date_validation']): ?>
                        <span class="badge badge-done ml-2">✔ Validé le <?= date('d/m/Y', strtotime($pf['date_validation'])) ?></span>
                    <?php else: ?>
                        <span class="badge badge-wip ml-2">⏳ À valider</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <?php if (!$pf['date_validation']): ?>
                    <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700 transition"
                            onclick="validerParcoursFinal(<?= $pf['id'] ?>, this)">
                        ✔ Valider
                    </button>
                    <?php endif; ?>
                    <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-red-200 text-red-600 hover:bg-red-50 transition"
                            onclick="supprimerParcoursFinal(<?= $pf['id'] ?>, this)">
                        🗑
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Formulaire ajout -->
            <div class="px-4 py-3 border-t border-amber-200">
                <button type="button"
                        class="text-sm font-semibold text-amber-700 hover:text-amber-900 flex items-center gap-1 mb-3"
                        onclick="togglePfForm(<?= $qid ?>)">
                    ➕ Ajouter un parcours final
                </button>
                <div id="pf-form-<?= $qid ?>" class="pf-form">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                        <input type="text" id="pf-titre-<?= $qid ?>" placeholder="Titre du parcours *"
                               class="border border-amber-300 rounded-lg px-3 py-2 text-sm col-span-1 sm:col-span-2 focus:outline-none focus:border-amber-500">
                        <input type="text" id="pf-ville-<?= $qid ?>" placeholder="Ville"
                               class="border border-amber-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-500">
                        <input type="number" id="pf-km-<?= $qid ?>" placeholder="Distance (km)" step="0.1"
                               class="border border-amber-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-500">
                    </div>
                    <button type="button"
                            onclick="ajouterParcoursFinal(<?= $qid ?>)"
                            class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                        💾 Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div><!-- .quete-card -->
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══════════ MODALS ══════════ -->
<div id="modal-confirm-objet" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
        <p class="text-4xl mb-3">🎒</p>
        <h2 class="text-xl font-bold text-slate-800 mb-2">Confirmer l'obtention ?</h2>
        <p class="text-sm text-slate-500 mb-1" id="modal-objet-quete"></p>
        <p class="text-base font-bold text-indigo-700 mb-4" id="modal-objet-nom"></p>
        <p class="text-sm text-slate-500 mb-6">
            En validant un parcours lié à cet objet, vous confirmez l'avoir obtenu physiquement.
        </p>
        <div class="flex gap-3">
            <button onclick="closeModalObjet()"
                    class="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold transition">
                Pas encore
            </button>
            <button id="modal-objet-confirm-btn"
                    class="flex-1 px-4 py-3 rounded-xl bg-amber-500 text-white hover:bg-amber-600 font-bold transition">
                ✔ Oui, obtenu !
            </button>
        </div>
    </div>
</div>

<script>
// ── Accordéon objet ────────────────────────────────────────────────
function toggleObjet(id) {
    const btn  = document.querySelector('[aria-controls="body-' + id + '"]');
    const body = document.getElementById('body-' + id);
    if (!btn || !body) return;
    const isOpen = btn.getAttribute('aria-expanded') === 'true';
    if (isOpen) {
        body.style.maxHeight = body.scrollHeight + 'px';
        requestAnimationFrame(() => { body.style.maxHeight = '0'; body.style.opacity = '0'; });
        btn.setAttribute('aria-expanded', 'false');
        body.classList.remove('open');
    } else {
        body.style.maxHeight = body.scrollHeight + 'px';
        body.style.opacity   = '1';
        btn.setAttribute('aria-expanded', 'true');
        body.classList.add('open');
        body.addEventListener('transitionend', () => {
            if (btn.getAttribute('aria-expanded') === 'true') body.style.maxHeight = 'none';
        }, { once: true });
    }
}

// ── Accordéon parcours final ────────────────────────────────────────
function toggleFinal(qid) {
    const body    = document.getElementById('final-body-' + qid);
    const chevron = document.getElementById('final-chevron-' + qid);
    if (!body) return;
    const open = body.style.display !== 'none';
    body.style.display    = open ? 'none' : 'block';
    chevron.textContent   = open ? '▼' : '▲';
}

function togglePfForm(qid) {
    const f = document.getElementById('pf-form-' + qid);
    if (f) f.classList.toggle('open');
}

// ── Confirmer objet ────────────────────────────────────────────────
let _pendingObjetId = null;

function confirmerObjet(objetId, objetNom, queteId) {
    _pendingObjetId = objetId;
    const queteEl = document.getElementById('quete-' + queteId);
    document.getElementById('modal-objet-quete').textContent = queteEl ? queteEl.querySelector('h2')?.textContent?.trim() : '';
    document.getElementById('modal-objet-nom').textContent   = objetNom;
    document.getElementById('modal-confirm-objet').classList.remove('hidden');
}

function closeModalObjet() {
    document.getElementById('modal-confirm-objet').classList.add('hidden');
    _pendingObjetId = null;
}

document.getElementById('modal-objet-confirm-btn').addEventListener('click', async function () {
    if (!_pendingObjetId) return;
    this.disabled = true;
    this.textContent = '⏳ En cours…';
    try {
        const res = await taFetch('/api/quetes/confirmer-objet', 'POST', { quete_objet_id: _pendingObjetId });
        closeModalObjet();
        if (res.quete_debloquee) {
            taToast('success', '🔓 Quête débloquée ! Renseignez le parcours final.');
        } else {
            taToast('success', 'Objet confirmé !');
        }
        setTimeout(() => location.reload(), 900);
    } catch (e) {
        taToast('error', e.message || 'Erreur lors de la confirmation.');
        this.disabled = false;
        this.textContent = '✔ Oui, obtenu !';
    }
});

// ── Parcours final : ajouter ────────────────────────────────────────
async function ajouterParcoursFinal(qid) {
    const titre = document.getElementById('pf-titre-' + qid)?.value?.trim();
    const ville = document.getElementById('pf-ville-' + qid)?.value?.trim();
    const km    = document.getElementById('pf-km-' + qid)?.value?.trim();
    if (!titre) { taToast('error', 'Le titre est obligatoire.'); return; }
    try {
        await taFetch('/api/quetes/parcours-final/ajouter', 'POST', { quete_id: qid, titre, ville, distance_km: km });
        taToast('success', 'Parcours final ajouté !');
        setTimeout(() => location.reload(), 800);
    } catch (e) { taToast('error', e.message || 'Erreur.'); }
}

// ── Parcours final : valider ────────────────────────────────────────
async function validerParcoursFinal(pfId, btn) {
    const ok = await taConfirm('Valider ce parcours final ?');
    if (!ok) return;
    btn.disabled = true;
    try {
        await taFetch('/api/quetes/parcours-final/valider', 'POST', { parcours_final_id: pfId });
        taToast('success', 'Parcours final validé !');
        setTimeout(() => location.reload(), 800);
    } catch (e) { taToast('error', e.message || 'Erreur.'); btn.disabled = false; }
}

// ── Parcours final : supprimer ──────────────────────────────────────
async function supprimerParcoursFinal(pfId, btn) {
    const ok = await taConfirm('Supprimer ce parcours final ?', { okColor: '#dc2626', okLabel: 'Supprimer' });
    if (!ok) return;
    btn.disabled = true;
    try {
        await taFetch('/api/quetes/parcours-final/supprimer', 'POST', { parcours_final_id: pfId });
        taToast('success', 'Supprimé.');
        setTimeout(() => location.reload(), 700);
    } catch (e) { taToast('error', e.message || 'Erreur.'); btn.disabled = false; }
}

// ── Helper fetch JSON (utilise le token de session via cookie) ──────
async function taFetch(url, method = 'GET', body = null) {
    const opts = { method, headers: {} };
    if (body) {
        opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        opts.body = new URLSearchParams(body).toString();
    }
    const res  = await fetch(url, opts);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Erreur serveur');
    return data;
}

// ── Suppression quête (admin) ───────────────────────────────────────
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-delete-quete]');
    if (!btn) return;
    const ok = await taConfirm('Supprimer la quête "' + btn.dataset.queteNom + '" ?', {
        sub: 'Cette action est irréversible.', icon: '🗑️', okLabel: 'Supprimer', okColor: '#dc2626'
    });
    if (!ok) return;
    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/admin/quetes/delete';
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'id'; inp.value = btn.dataset.deleteQuete;
    form.appendChild(inp);
    document.body.appendChild(form);
    form.submit();
});
</script>