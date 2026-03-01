<?php
// $zamelas = résultat de Parcours::getAllWithFilters() avec zamela_only=true
// Même structure que les parcours normaux + date_debut / date_fin
$user    = $_SESSION['user'] ?? null;
$isAdmin = isset($user['role']) && $user['role'] === 'admin';

$totalCount = count($zamelas ?? []);
$doneCount  = 0;

foreach (($zamelas ?? []) as $z) {
    if ($z['effectue']) $doneCount++;
}

$niveauLabel = fn($n) => match((int)$n) {
    1 => ['Facile',       'bg-green-100 text-green-700'],
    2 => ['Modéré',       'bg-lime-100 text-lime-700'],
    3 => ['Intermédiaire','bg-yellow-100 text-yellow-700'],
    4 => ['Difficile',    'bg-orange-100 text-orange-700'],
    5 => ['Expert',       'bg-red-100 text-red-700'],
    default => ['—',      'bg-gray-100 text-gray-500'],
};

if (!function_exists('zIsExpired')) {
function zIsExpired(?string $fin): bool {
    if (!$fin) return false;
    return new DateTime($fin) < new DateTime('today');
}
function zIsActive(?string $debut, ?string $fin): bool {
    if (!$debut || !$fin) return false;
    $today = new DateTime('today');
    return new DateTime($debut) <= $today && $today <= new DateTime($fin);
}
function zFormatDate(?string $d): string {
    if (!$d) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt ? $dt->format('d/m/Y') : $d;
}
} // end if (!function_exists('zIsExpired'))
?>

<style>
.zamela-card {
    background: #fff; border-radius: 1rem; border: 1px solid #f1f5f9;
    padding: 1.1rem 1.25rem; display: flex; align-items: center;
    justify-content: space-between; gap: 1.25rem;
    transition: box-shadow .18s, transform .18s;
}
.zamela-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); transform: translateY(-1px); }
.zamela-card.done     { border-left: 4px solid #7c3aed; }
.zamela-card.expired  { border-left: 4px solid #94a3b8; opacity: .7; }
.zamela-card.active-now { border-left: 4px solid #16a34a; }

.poiz-logo { width: 2.75rem; height: 2.75rem; object-fit: contain; border-radius: .5rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: .2rem; flex-shrink: 0; }
.poiz-placeholder { width: 2.75rem; height: 2.75rem; flex-shrink: 0; border-radius: .5rem; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #94a3b8; }
.niveau-badge { display: inline-flex; align-items: center; padding: .15rem .6rem; border-radius: 9999px; font-size: .7rem; font-weight: 600; white-space: nowrap; }
.date-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .6rem; border-radius: 9999px; font-size: .7rem; font-weight: 600; white-space: nowrap; }
.date-badge.active-now { background: #dcfce7; color: #15803d; }
.date-badge.expired    { background: #f1f5f9; color: #94a3b8; }
.date-badge.upcoming   { background: #fef3c7; color: #b45309; }
.btn-z { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; height: 2.4rem; min-width: 9rem; padding: 0 1.1rem; border-radius: .65rem; font-size: .82rem; font-weight: 600; border: none; cursor: pointer; transition: all .18s; white-space: nowrap; }
.btn-z.purple   { background: #7c3aed; color: #fff; }
.btn-z.purple:hover { background: #6d28d9; }
.btn-z.done     { background: #ede9fe; color: #6d28d9; border: 1.5px solid #c4b5fd; }
.btn-z.done:hover { background: #ddd6fe; }
.btn-z.disabled { background: #f1f5f9; color: #94a3b8; border: 1.5px solid #e2e8f0; cursor: not-allowed; }
.admin-btn { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: .5rem; font-size: .85rem; border: none; cursor: pointer; transition: all .15s; }
.admin-btn.edit   { background: #fef3c7; color: #b45309; }
.admin-btn.edit:hover   { background: #fde68a; }
.admin-btn.delete { background: #fee2e2; color: #dc2626; }
.admin-btn.delete:hover { background: #fecaca; }
</style>

<?php if (empty($zamelas)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <div class="text-4xl mb-3">⚡</div>
        <p class="text-gray-500 font-medium">Aucun Zaméla trouvé</p>
        <p class="text-sm text-gray-400 mt-1">Essayez de modifier vos filtres</p>
    </div>
<?php else: ?>

    <div class="flex items-center justify-between mb-3 px-1">
        <p class="text-sm text-gray-500">
            <span class="font-semibold text-gray-700"><?= $totalCount ?></span>
            Zaméla trouvé<?= $totalCount > 1 ? 's' : '' ?>
            <?php if ($doneCount > 0): ?>
                · <span class="text-violet-600 font-semibold"><?= $doneCount ?> effectué<?= $doneCount > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </p>
    </div>

    <div class="space-y-2.5">
    <?php foreach ($zamelas as $z): ?>
        <?php
        $isDone    = (bool)$z['effectue'];
        $isExpired = zIsExpired($z['date_fin'] ?? null);
        $isNow     = zIsActive($z['date_debut'] ?? null, $z['date_fin'] ?? null);

        if ($isDone)        $cardClass = 'done';
        elseif ($isExpired) $cardClass = 'expired';
        elseif ($isNow)     $cardClass = 'active-now';
        else                $cardClass = '';

        if ($isNow)         [$dateCls, $dateLabel] = ['active-now', '🟢 En cours'];
        elseif ($isExpired) [$dateCls, $dateLabel] = ['expired',    '⛔ Terminé'];
        else                [$dateCls, $dateLabel] = ['upcoming',   '🕐 À venir'];

        [$niveauTxt, $niveauCls] = $niveauLabel((int)($z['niveau'] ?? 0));
        ?>
        <div class="zamela-card <?= $cardClass ?>">

            <div class="flex items-center gap-3 min-w-0 flex-1">
                <?php if (!empty($z['poiz_logo'])): ?>
                    <img src="<?= htmlspecialchars($z['poiz_logo']) ?>" class="poiz-logo" alt="">
                <?php else: ?>
                    <div class="poiz-placeholder">⚡</div>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                        <span class="font-semibold text-gray-800 text-sm leading-snug truncate max-w-xs">
                            <?= htmlspecialchars($z['titre']) ?>
                        </span>
                        <span class="date-badge <?= $dateCls ?>"><?= $dateLabel ?></span>
                        <?php if ((int)($z['niveau'] ?? 0) > 0): ?>
                            <span class="niveau-badge <?= $niveauCls ?>"><?= $niveauTxt ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-400">
                        <span>📍 <?= htmlspecialchars($z['ville']) ?></span>
                        <span class="text-gray-300">·</span>
                        <span>📅 <?= zFormatDate($z['date_debut'] ?? null) ?> → <?= zFormatDate($z['date_fin'] ?? null) ?></span>
                        <?php if (!empty($z['distance_km'])): ?>
                            <span class="text-gray-300">·</span>
                            <span>📏 <?= number_format((float)$z['distance_km'], 1) ?> km</span>
                        <?php endif; ?>
                        <?php if (!empty($z['duree'])): ?>
                            <span class="text-gray-300">·</span>
                            <span>⏱ <?= htmlspecialchars($z['duree']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">

                <?php if ($isDone): ?>
                    <button class="btn-z done">⚡ Effectué</button>
                    <form method="post" action="/parcours/reset"
                          data-confirm="Réinitialiser ce Zaméla ?" data-confirm-icon="⚡" data-confirm-ok="Réinitialiser" data-confirm-color="#7c3aed">
                        <input type="hidden" name="parcours_id" value="<?= (int)$z['id'] ?>">
                        <input type="hidden" name="redirect" value="/zamela<?= isset($_GET['expires']) ? '?expires=1' : '' ?>">
                        <button type="submit" class="admin-btn delete" title="Réinitialiser">↺</button>
                    </form>
                <?php else: ?>
                    <button onclick="openZValidateModal(<?= (int)$z['id'] ?>)" class="btn-z purple">
                        ⚡ Valider
                    </button>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <a href="/zamela/edit?id=<?= (int)$z['id'] ?>" class="admin-btn edit" title="Modifier">✏️</a>
                    <form method="post" action="/zamela/delete"
                          data-confirm="Supprimer ce Zaméla ?" data-confirm-icon="🗑️" data-confirm-sub="Cette action est irréversible." data-confirm-ok="Supprimer">
                        <input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
                        <button type="submit" class="admin-btn delete" title="Supprimer">🗑</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>
    </div>

<?php endif; ?>
