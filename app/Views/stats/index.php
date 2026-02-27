<?php
$me = $_SESSION['user'];
$niveauLabel = fn($n) => match(true) {
    $n >= 4.5 => 'Expert', $n >= 3.5 => 'Difficile', $n >= 2.5 => 'Intermédiaire',
    $n >= 1.5 => 'Modéré', default => 'Facile',
};

function pct(int $done, int $total): int {
    return $total > 0 ? min(100, (int)round($done / $total * 100)) : 0;
}
function fmtDate(?string $d): string {
    if (!$d) return '—';
    $dt = new DateTime($d); return $dt->format('d/m/Y');
}
?>

<style>
.stat-card{background:#fff;border-radius:1.25rem;border:1px solid #f1f5f9;padding:1.25rem;transition:box-shadow .2s}
.stat-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
.stat-value{font-size:2rem;font-weight:800;line-height:1;color:#1e293b}
.stat-label{font-size:.75rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em}
.stat-sub{font-size:.78rem;color:#64748b;margin-top:.25rem}
.progress-bar-track{height:.45rem;background:#f1f5f9;border-radius:9999px;overflow:hidden}
.progress-bar-fill{height:100%;border-radius:9999px;transition:width .8s ease}
.poiz-row{display:flex;align-items:center;gap:.75rem;padding:.6rem 0;border-bottom:1px solid #f8fafc}
.poiz-row:last-child{border-bottom:none}
.poiz-logo-sm{width:2rem;height:2rem;object-fit:contain;border-radius:.4rem;background:#f8fafc;border:1px solid #e2e8f0;padding:.15rem;flex-shrink:0}
.compare-col{flex:1;min-width:0}
.medal{font-size:1rem}
.section-title{font-size:.9rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
</style>

<!-- HEADER -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">📊 Statistiques</h1>
        <p class="text-sm text-gray-500">Votre progression sur Terra Aventura</p>
    </div>

    <!-- Comparaison -->
    <form method="get" action="/stats" class="flex items-center gap-2">
        <select name="compare"
                onchange="this.form.submit()"
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm bg-white focus:border-blue-500 outline-none">
            <option value="">👤 Comparer avec…</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (int)($_GET['compare'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($compareUser): ?>
            <a href="/stats" class="text-sm text-red-500 hover:text-red-700 ml-1">✕</a>
        <?php endif; ?>
    </form>
</div>

<!-- BANDEAU SCORE GLOBAL -->
<div class="rounded-2xl p-5 mb-6 flex flex-col sm:flex-row items-center gap-5"
     style="background:linear-gradient(135deg,#1e40af,#3b82f6)">
    <div class="flex-1 text-white">
        <p class="text-blue-200 text-sm mb-1">Score global</p>
        <div class="text-4xl font-black"><?= $myStats['score_global'] ?>%</div>
        <p class="text-blue-200 text-xs mt-1">Toutes catégories confondues</p>
    </div>
    <?php if ($compareStats): ?>
    <div class="flex-1 text-white text-right">
        <p class="text-blue-200 text-sm mb-1"><?= htmlspecialchars($compareUser['username']) ?></p>
        <div class="text-4xl font-black"><?= $compareStats['score_global'] ?>%</div>
        <p class="text-blue-200 text-xs mt-1">Score global</p>
    </div>
    <?php endif; ?>
    <div class="w-24 h-24 flex-shrink-0">
        <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
            <circle cx="18" cy="18" r="15.9" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="3"/>
            <circle cx="18" cy="18" r="15.9" fill="none" stroke="white" stroke-width="3"
                    stroke-dasharray="<?= $myStats['score_global'] ?> 100" stroke-linecap="round"/>
        </svg>
    </div>
</div>

<!-- GRILLE STATS PRINCIPALES -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <!-- Parcours permanents -->
    <div class="stat-card">
        <p class="stat-label">🗺️ Parcours</p>
        <div class="flex items-end gap-2 mt-1">
            <div class="stat-value text-blue-600"><?= $myStats['parcours_effectues'] ?></div>
            <div class="text-sm text-gray-400 mb-1">/ <?= $myStats['parcours_total'] ?></div>
            <?php if ($compareStats): ?>
                <div class="ml-auto text-right">
                    <div class="text-lg font-bold text-gray-400"><?= $compareStats['parcours_effectues'] ?></div>
                    <div class="text-xs text-gray-300">/ <?= $compareStats['parcours_total'] ?></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="progress-bar-track mt-2">
            <div class="progress-bar-fill bg-blue-500" style="width:<?= pct($myStats['parcours_effectues'], $myStats['parcours_total']) ?>%"></div>
        </div>
        <p class="stat-sub"><?= pct($myStats['parcours_effectues'], $myStats['parcours_total']) ?>% complétés</p>
    </div>

    <!-- Zaméla -->
    <div class="stat-card">
        <p class="stat-label">⚡ Zaméla</p>
        <div class="flex items-end gap-2 mt-1">
            <div class="stat-value text-violet-600"><?= $myStats['zamela_effectues'] ?></div>
            <div class="text-sm text-gray-400 mb-1">/ <?= $myStats['zamela_total'] ?></div>
            <?php if ($compareStats): ?>
                <div class="ml-auto text-right">
                    <div class="text-lg font-bold text-gray-400"><?= $compareStats['zamela_effectues'] ?></div>
                    <div class="text-xs text-gray-300">/ <?= $compareStats['zamela_total'] ?></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="progress-bar-track mt-2">
            <div class="progress-bar-fill" style="width:<?= pct($myStats['zamela_effectues'], $myStats['zamela_total']) ?>%;background:#7c3aed"></div>
        </div>
        <p class="stat-sub"><?= pct($myStats['zamela_effectues'], $myStats['zamela_total']) ?>% complétés</p>
    </div>

    <!-- Événements -->
    <div class="stat-card">
        <p class="stat-label">🎉 Événements</p>
        <div class="flex items-end gap-2 mt-1">
            <div class="stat-value text-orange-500"><?= $myStats['evenements_effectues'] ?></div>
            <div class="text-sm text-gray-400 mb-1">/ <?= $myStats['evenements_total'] ?></div>
            <?php if ($compareStats): ?>
                <div class="ml-auto text-right">
                    <div class="text-lg font-bold text-gray-400"><?= $compareStats['evenements_effectues'] ?></div>
                    <div class="text-xs text-gray-300">/ <?= $compareStats['evenements_total'] ?></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="progress-bar-track mt-2">
            <div class="progress-bar-fill" style="width:<?= pct($myStats['evenements_effectues'], $myStats['evenements_total']) ?>%;background:#f97316"></div>
        </div>
        <p class="stat-sub"><?= pct($myStats['evenements_effectues'], $myStats['evenements_total']) ?>% participés</p>
    </div>

    <!-- Divers -->
    <div class="stat-card">
        <p class="stat-label">🏅 Badges</p>
        <div class="stat-value text-amber-500 mt-1"><?= $myStats['badges'] ?></div>
        <p class="stat-sub">
            📏 <?= number_format($myStats['distance_km'], 1) ?> km parcourus
        </p>
        <?php if ($compareStats): ?>
        <p class="text-xs text-gray-300 mt-1">vs <?= $compareStats['badges'] ?> badges · <?= number_format($compareStats['distance_km'],1) ?> km</p>
        <?php endif; ?>
    </div>

</div>

<!-- 2 colonnes : Classement POIZ + Infos diverses -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- Classement par POIZ -->
    <div class="stat-card">
        <p class="section-title">📍 Classement par POIZ</p>
        <?php if (empty($myStats['par_poiz'])): ?>
            <p class="text-sm text-gray-400 text-center py-6">Aucun parcours effectué</p>
        <?php else: ?>
            <div class="max-h-72 overflow-y-auto pr-1">
            <?php foreach ($myStats['par_poiz'] as $i => $pz): ?>
                <?php
                $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '' };
                $p = pct((int)$pz['nb_effectues'], (int)$pz['nb_total']);
                ?>
                <div class="poiz-row">
                    <span class="text-base w-5 text-center flex-shrink-0"><?= $medal ?: ($i+1) ?></span>
                    <?php if (!empty($pz['poiz_logo'])): ?>
                        <img src="<?= htmlspecialchars($pz['poiz_logo']) ?>" class="poiz-logo-sm" alt="">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium text-gray-700 truncate"><?= htmlspecialchars($pz['poiz_nom']) ?></span>
                            <span class="text-xs text-gray-400 flex-shrink-0"><?= $pz['nb_effectues'] ?>/<?= $pz['nb_total'] ?></span>
                        </div>
                        <div class="progress-bar-track mt-1">
                            <div class="progress-bar-fill bg-blue-500" style="width:<?= $p ?>%"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Infos complémentaires -->
    <div class="space-y-4">

        <!-- Département favori -->
        <div class="stat-card">
            <p class="section-title">📍 Département favori</p>
            <?php if ($myStats['dept_favori']): ?>
                <div class="text-lg font-bold text-gray-800">
                    <?= htmlspecialchars($myStats['dept_favori']['departement_code']) ?>
                    <?php if (!empty($myStats['dept_favori']['departement_nom'])): ?>
                        — <?= htmlspecialchars($myStats['dept_favori']['departement_nom']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($compareStats && $compareStats['dept_favori']): ?>
                    <p class="text-xs text-gray-400 mt-1">
                        <?= htmlspecialchars($compareUser['username']) ?> : <?= htmlspecialchars($compareStats['dept_favori']['departement_code']) ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-sm text-gray-400">—</p>
            <?php endif; ?>
        </div>

        <!-- Niveau moyen + dates -->
        <div class="stat-card">
            <p class="section-title">📈 Activité</p>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-gray-400 text-xs">Niveau moyen</p>
                    <p class="font-semibold text-gray-700"><?= $myStats['niveau_moyen'] > 0 ? $myStats['niveau_moyen'] . '/5 — ' . $niveauLabel($myStats['niveau_moyen']) : '—' ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Parcours d'événements</p>
                    <p class="font-semibold text-gray-700"><?= $myStats['ep_effectues'] ?> effectués</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Premier parcours</p>
                    <p class="font-semibold text-gray-700"><?= fmtDate($myStats['premier_parcours']) ?></p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Dernier parcours</p>
                    <p class="font-semibold text-gray-700"><?= fmtDate($myStats['dernier_parcours']) ?></p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- DERNIERS PARCOURS EFFECTUÉS -->
<?php if (!empty($myStats['recents'])): ?>
<div class="stat-card">
    <p class="section-title">🕐 Derniers parcours effectués</p>
    <div class="space-y-2">
    <?php foreach ($myStats['recents'] as $r): ?>
        <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
            <?php if (!empty($r['poiz_logo'])): ?>
                <img src="<?= htmlspecialchars($r['poiz_logo']) ?>" class="w-8 h-8 object-contain rounded border bg-gray-50 p-0.5 flex-shrink-0" alt="">
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-700 truncate"><?= htmlspecialchars($r['titre']) ?></p>
                <p class="text-xs text-gray-400">📍 <?= htmlspecialchars($r['ville']) ?> · <?= htmlspecialchars($r['poiz_nom']) ?></p>
            </div>
            <span class="text-xs text-gray-400 flex-shrink-0"><?= fmtDate($r['date_validation']) ?></span>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
