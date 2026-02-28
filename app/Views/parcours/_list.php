<?php
$user    = $_SESSION['user'] ?? null;
$isAdmin = isset($user['role']) && $user['role'] === 'admin';
$userId  = (int)($user['id'] ?? 0);

$grouped     = [];
$totalCount  = count($parcours);
$doneCount   = 0;

foreach ($parcours as $p) {
    $grouped[$p['departement_code']][] = $p;
    if ($p['effectue']) $doneCount++;
}

$currentPage = max(1, (int)($_GET['page'] ?? 1));

$niveauLabel = fn($n) => match((int)$n) {
    1 => ['Facile',      'bg-green-100 text-green-700'],
    2 => ['Modéré',      'bg-lime-100 text-lime-700'],
    3 => ['Intermédiaire','bg-yellow-100 text-yellow-700'],
    4 => ['Difficile',   'bg-orange-100 text-orange-700'],
    5 => ['Expert',      'bg-red-100 text-red-700'],
    default => ['—',     'bg-gray-100 text-gray-500'],
};
?>

<style>
.parcours-card {
    background: #fff;
    border-radius: 1rem;
    border: 1px solid #f1f5f9;
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    transition: box-shadow .18s, transform .18s;
}
.parcours-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); transform: translateY(-1px); }
.parcours-card.done { border-left: 4px solid #16a34a; }
.parcours-card.maintenance { border-left: 4px solid #f59e0b; opacity: .85; }

.poiz-logo {
    width: 2.75rem; height: 2.75rem;
    object-fit: contain; border-radius: .5rem;
    background: #f8fafc; border: 1px solid #e2e8f0;
    padding: .2rem; flex-shrink: 0;
}
.poiz-placeholder {
    width: 2.75rem; height: 2.75rem; flex-shrink: 0;
    border-radius: .5rem; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #94a3b8;
}

.niveau-badge {
    display: inline-flex; align-items: center;
    padding: .15rem .6rem; border-radius: 9999px;
    font-size: .7rem; font-weight: 600; white-space: nowrap;
}

.btn-valider {
    display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
    height: 2.4rem; min-width: 9rem; padding: 0 1.1rem;
    border-radius: .65rem; font-size: .82rem; font-weight: 600;
    border: none; cursor: pointer; transition: all .18s; white-space: nowrap;
}
.btn-valider.green  { background: #16a34a; color: #fff; }
.btn-valider.green:hover { background: #15803d; }
.btn-valider.done   { background: #dcfce7; color: #15803d; border: 1.5px solid #86efac; }
.btn-valider.done:hover { background: #bbf7d0; }
.btn-valider.disabled { background: #f1f5f9; color: #94a3b8; border: 1.5px solid #e2e8f0; cursor: not-allowed; }

.admin-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem; border-radius: .5rem;
    font-size: .85rem; border: none; cursor: pointer; transition: all .15s;
}
.admin-btn.edit  { background: #fef3c7; color: #b45309; }
.admin-btn.edit:hover  { background: #fde68a; }
.admin-btn.delete { background: #fee2e2; color: #dc2626; }
.admin-btn.delete:hover { background: #fecaca; }

.dept-header {
    display: flex; align-items: center; gap: .6rem;
    font-size: .82rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .06em;
    padding: .3rem 0 .6rem;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: .75rem;
}
.dept-dot {
    width: .5rem; height: .5rem; border-radius: 50%; background: #cbd5e1; flex-shrink: 0;
}

/* Pagination */
.page-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2.2rem; height: 2.2rem; padding: 0 .6rem;
    border-radius: .5rem; border: 1.5px solid #e2e8f0;
    background: #fff; color: #475569; font-size: .82rem; font-weight: 500;
    cursor: pointer; transition: all .15s;
}
.page-btn:hover:not(.active) { background: #f8fafc; border-color: #cbd5e1; }
.page-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }
.page-btn.arrow  { font-size: 1rem; }
</style>

<?php if (empty($parcours)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <div class="text-4xl mb-3">🗺️</div>
        <p class="text-gray-500 font-medium">Aucun parcours trouvé</p>
        <p class="text-sm text-gray-400 mt-1">Essayez de modifier vos filtres</p>
    </div>
<?php else: ?>

    <!-- Compteur résultats -->
    <div class="flex items-center justify-between mb-3 px-1">
        <p class="text-sm text-gray-500">
            <span class="font-semibold text-gray-700"><?= $totalCount ?></span>
            parcours trouvé<?= $totalCount > 1 ? 's' : '' ?>
            <?php if ($doneCount > 0): ?>
                · <span class="text-green-600 font-semibold"><?= $doneCount ?> effectué<?= $doneCount > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </p>
    </div>

    <?php foreach ($grouped as $dept => $items): ?>
        <div class="mb-6">
            <!-- En-tête département -->
            <div class="dept-header">
                <span class="dept-dot"></span>
                Département <?= htmlspecialchars($dept) ?>
                <span class="ml-auto font-normal text-gray-400 normal-case tracking-normal"><?= count($items) ?> parcours</span>
            </div>

            <div class="space-y-2.5">
            <?php foreach ($items as $p): ?>
                <?php
                $isDone  = (bool)$p['effectue'];
                $isMaint = !empty($p['en_maintenance']);
                $cardClass = $isDone ? 'done' : ($isMaint && !$isDone ? 'maintenance' : '');
                [$niveauTxt, $niveauCls] = $niveauLabel((int)($p['niveau'] ?? 0));
                ?>
                <div class="parcours-card <?= $cardClass ?>">

                    <!-- Logo + infos -->
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <?php if (!empty($p['poiz_logo'])): ?>
                            <img src="<?= htmlspecialchars($p['poiz_logo']) ?>" class="poiz-logo" alt="">
                        <?php else: ?>
                            <div class="poiz-placeholder">?</div>
                        <?php endif; ?>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                <span class="font-semibold text-gray-800 text-sm leading-snug truncate max-w-xs">
                                    <?= htmlspecialchars($p['titre']) ?>
                                </span>
                                <?php if ($isMaint && !$isDone): ?>
                                    <span class="niveau-badge bg-amber-100 text-amber-700">🛠 Maintenance</span>
                                <?php endif; ?>
                                <?php if ((int)($p['niveau'] ?? 0) > 0): ?>
                                    <span class="niveau-badge <?= $niveauCls ?>"><?= $niveauTxt ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-400">
                                <span>📍 <?= htmlspecialchars($p['ville']) ?></span>
                                <?php if (!empty($p['poiz_nom'])): ?>
                                    <span class="text-gray-300">·</span>
                                    <span><?= htmlspecialchars($p['poiz_nom']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['distance_km'])): ?>
                                    <span class="text-gray-300">·</span>
                                    <span>📏 <?= number_format((float)$p['distance_km'], 1) ?> km</span>
                                <?php endif; ?>
                                <?php if (!empty($p['duree'])): ?>
                                    <span class="text-gray-300">·</span>
                                    <span>⏱ <?= htmlspecialchars($p['duree']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 flex-shrink-0">

                        <?php if ($isDone): ?>
                            <a href="/parcours/effectue?id=<?= (int)$p['id'] ?>" class="btn-valider done">
                                ✔ Effectué
                            </a>
                        <?php elseif ($isMaint): ?>
                            <button class="btn-valider disabled" disabled title="Parcours en maintenance">
                                🛠 Indisponible
                            </button>
                        <?php else: ?>
                            <button onclick="openValidateModal(<?= (int)$p['id'] ?>)" class="btn-valider green">
                                ✚ Valider
                            </button>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                            <a href="/parcours/edit?id=<?= (int)$p['id'] ?>" class="admin-btn edit" title="Modifier">✏️</a>
                            <form method="post" action="/parcours/delete"
                                  data-confirm="Supprimer ce parcours ?" data-confirm-icon="🗑️" data-confirm-sub="Cette action est irréversible." data-confirm-ok="Supprimer">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="admin-btn delete" title="Supprimer">🗑</button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Pagination -->
    <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <div class="flex justify-center items-center gap-1.5 mt-8 flex-wrap">

            <?php if ($currentPage > 1): ?>
                <button onclick="loadParcours('<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>')"
                        class="page-btn arrow">←</button>
            <?php endif; ?>

            <?php for ($i = max(1, $currentPage - 3); $i <= min($totalPages, $currentPage + 3); $i++): ?>
                <button onclick="loadParcours('<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>')"
                        class="page-btn <?= $i == $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </button>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <button onclick="loadParcours('<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>')"
                        class="page-btn arrow">→</button>
            <?php endif; ?>

        </div>
    <?php endif; ?>

<?php endif; ?>
