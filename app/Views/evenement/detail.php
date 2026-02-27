<?php
$niveauLabel = fn($n) => match((int)$n) {
    1 => ['Facile','bg-green-100 text-green-700'],
    2 => ['Modéré','bg-lime-100 text-lime-700'],
    3 => ['Intermédiaire','bg-yellow-100 text-yellow-700'],
    4 => ['Difficile','bg-orange-100 text-orange-700'],
    5 => ['Expert','bg-red-100 text-red-700'],
    default => ['—','bg-gray-100 text-gray-500'],
};
$isExpired = new DateTime($evt['date_fin']) < new DateTime('today');
?>

<div class="max-w-3xl mx-auto">

    <!-- En-tête événement -->
    <div class="bg-white rounded-2xl border shadow-sm overflow-hidden mb-6">
        <?php if (!empty($evt['image'])): ?>
            <div class="h-40 overflow-hidden">
                <img src="<?= htmlspecialchars($evt['image']) ?>" class="w-full h-full object-cover" alt="">
            </div>
        <?php endif; ?>
        <div class="p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($evt['nom']) ?></h1>
                    <p class="text-sm text-gray-500">
                        📍 <?= htmlspecialchars($evt['ville']) ?> (<?= $evt['departement_code'] ?>)
                        · 📅 <?= (new DateTime($evt['date_debut']))->format('d/m/Y') ?>
                        → <?= (new DateTime($evt['date_fin']))->format('d/m/Y') ?>
                    </p>
                </div>
                <a href="/evenement" class="text-sm text-gray-400 hover:text-gray-700">← Retour</a>
            </div>

            <!-- Statut participation globale -->
            <?php if (!$isAdmin): ?>
            <div class="mt-4 pt-4 border-t">
                <?php if ($evt['effectue']): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-green-600 font-semibold text-sm">🎉 Vous avez participé à cet événement</span>
                        <form method="post" action="/evenement/reset">
                            <input type="hidden" name="evenement_id" value="<?= $evt['id'] ?>">
                            <button type="submit" class="text-xs text-red-500 hover:underline">Annuler</button>
                        </form>
                    </div>
                <?php elseif (!$isExpired): ?>
                    <form method="post" action="/evenement/valider" class="flex items-end gap-3">
                        <input type="hidden" name="evenement_id" value="<?= $evt['id'] ?>">
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Date de participation</label>
                            <input type="date" name="date" required
                                   class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-orange-500 outline-none">
                        </div>
                        <button type="submit"
                                class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm font-medium hover:bg-orange-600">
                            🎉 Valider participation
                        </button>
                    </form>
                <?php else: ?>
                    <span class="text-sm text-gray-400">Cet événement est terminé</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Parcours de l'événement -->
    <?php if (empty($evt['parcours'])): ?>
        <div class="bg-white rounded-2xl border p-10 text-center text-gray-400">
            <div class="text-3xl mb-2">🗺️</div>
            <p>Aucun parcours pour cet événement</p>
        </div>
    <?php else: ?>
        <h2 class="text-base font-semibold text-gray-700 mb-3">
            🗺️ Parcours de l'événement
            <span class="text-gray-400 font-normal text-sm">(<?= count($evt['parcours']) ?>)</span>
        </h2>
        <div class="space-y-3">
        <?php foreach ($evt['parcours'] as $p):
            [$nTxt, $nCls] = $niveauLabel($p['niveau'] ?? 0);
            $pDone = (bool)($p['effectue'] ?? false);
        ?>
            <div class="bg-white rounded-xl border <?= $pDone ? 'border-l-4 border-orange-400' : '' ?> p-4 flex items-center justify-between gap-4 shadow-sm hover:shadow transition">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($p['titre']) ?></span>
                        <?php if ((int)($p['niveau'] ?? 0) > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $nCls ?>"><?= $nTxt ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap gap-x-3 text-xs text-gray-400">
                        <?php if (!empty($p['distance_km'])): ?><span>📏 <?= number_format((float)$p['distance_km'], 1) ?> km</span><?php endif; ?>
                        <?php if (!empty($p['duree'])): ?><span>⏱ <?= htmlspecialchars($p['duree']) ?></span><?php endif; ?>
                    </div>
                </div>

                <?php if (!$isAdmin): ?>
                <div class="flex-shrink-0">
                    <?php if ($pDone): ?>
                        <form method="post" action="/evenement/reset-parcours">
                            <input type="hidden" name="ep_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="evenement_id" value="<?= $evt['id'] ?>">
                            <button type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-50 text-orange-600 border border-orange-200 rounded-lg text-xs font-medium hover:bg-orange-100">
                                ✔ Effectué
                            </button>
                        </form>
                    <?php elseif ($isExpired): ?>
                        <button disabled class="px-3 py-1.5 bg-gray-100 text-gray-400 rounded-lg text-xs">⛔ Terminé</button>
                    <?php else: ?>
                        <button onclick="openEpModal(<?= $p['id'] ?>)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-500 text-white rounded-lg text-xs font-medium hover:bg-orange-600">
                            ✚ Valider
                        </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs text-gray-300">ID <?= $p['id'] ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL VALIDATION PARCOURS -->
<div id="epModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-800">✚ Valider ce parcours</h3>
            <button onclick="closeEpModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 text-2xl">×</button>
        </div>
        <form method="post" action="/evenement/valider-parcours" class="space-y-4">
            <input type="hidden" name="ep_id" id="epModalId">
            <input type="hidden" name="evenement_id" value="<?= $evt['id'] ?>">
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Date</label>
                <input type="date" name="date" required
                       class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-orange-500 outline-none">
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t">
                <button type="button" onclick="closeEpModal()" class="px-4 py-2 border rounded-xl text-sm hover:bg-gray-50">Annuler</button>
                <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-xl text-sm font-medium hover:bg-orange-600">✚ Confirmer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEpModal(id) {
    document.getElementById('epModalId').value = id;
    const m = document.getElementById('epModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function closeEpModal() {
    const m = document.getElementById('epModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
document.getElementById('epModal').addEventListener('click', e => { if(e.target===e.currentTarget) closeEpModal(); });
</script>
