<?php
$user = $_SESSION['user'] ?? null;
$isAdmin = isset($user['role']) && $user['role'] === 'admin';

$grouped = [];
foreach ($parcours as $p) {
    $grouped[$p['departement_code']][] = $p;
}

$currentPage = max(1, (int)($_GET['page'] ?? 1));
?>

<?php if (empty($parcours)): ?>
    <div class="bg-white rounded-xl shadow-sm border p-6 text-center text-gray-500">
        Aucun parcours trouvé.
    </div>
<?php endif; ?>

<?php foreach ($grouped as $dept => $items): ?>
    <div class="space-y-4 mb-6">
        <h2 class="text-lg font-semibold">Département <?= htmlspecialchars($dept) ?></h2>

        <?php foreach ($items as $p): ?>
            <div class="bg-white rounded-xl shadow-sm border p-5
                        flex items-center justify-between gap-6">

                <!-- INFOS -->
                <div class="flex items-center gap-4">

                    <?php if (!empty($p['poiz_logo'])): ?>
                        <img src="<?= htmlspecialchars($p['poiz_logo']) ?>"
                             class="w-12 h-12 object-contain">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gray-200
                                    flex items-center justify-center">?</div>
                    <?php endif; ?>

                    <div>
                        <div class="font-semibold flex items-center gap-2">
                            <?= htmlspecialchars($p['titre']) ?>

                            <?php if (!empty($p['en_maintenance'])): ?>
                                <span class="px-2 py-1 text-xs bg-red-500 text-white rounded">
                                    🛠 En maintenance
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="text-sm text-gray-500">
                            📍 <?= htmlspecialchars($p['ville']) ?> (<?= htmlspecialchars($dept) ?>)
                        </div>

                        <div class="text-xs text-gray-400">
                            <?= htmlspecialchars($p['poiz_nom']) ?>
                        </div>
                    </div>
                </div>

                <!-- ACTION -->
                <div class="flex flex-col items-end gap-2 min-w-[160px]">

                    <?php if ($p['effectue']): ?>
                        <a href="/parcours/effectue?id=<?= (int)$p['id'] ?>"
                           class="h-10 w-full flex items-center justify-center
                                  bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
                            ✔ Effectué
                        </a>

                    <?php elseif (!empty($p['en_maintenance'])): ?>
                        <button disabled
                            class="h-10 w-full bg-gray-400 text-white rounded-lg text-sm font-medium cursor-not-allowed">
                            Indisponible
                        </button>

                    <?php else: ?>
                        <button type="button"
                                onclick="openValidateModal(<?= (int)$p['id'] ?>)"
                                class="h-10 w-full bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                            Valider
                        </button>
                    <?php endif; ?>

                    <!-- 🔧 ADMIN BUTTONS FIX -->
                    <?php if ($isAdmin): ?>
                        <div class="flex gap-2 mt-2">
                            <a href="/parcours/edit?id=<?= $p['id'] ?>"
                               class="w-10 h-10 bg-yellow-500 text-white rounded-lg flex items-center justify-center hover:bg-yellow-600 transition">
                                ✏️
                            </a>

                            <form method="post"
                                  action="/parcours/delete"
                                  onsubmit="return confirm('Supprimer ce parcours ?')">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="w-10 h-10 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<!-- 📄 PAGINATION AJAX -->
<?php if (!empty($totalPages) && $totalPages > 1): ?>
    <div class="flex justify-center items-center gap-2 mt-8 flex-wrap">

        <?php if ($currentPage > 1): ?>
            <button onclick="loadParcours('<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>')"
                    class="px-4 py-2 rounded-lg border bg-white hover:bg-gray-100 text-sm">
                ←
            </button>
        <?php endif; ?>

        <?php for ($i = max(1, $currentPage - 3); $i <= min($totalPages, $currentPage + 3); $i++): ?>
            <button onclick="loadParcours('<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>')"
                    class="px-4 py-2 rounded-lg border text-sm
                    <?= ($i == $currentPage) ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-100' ?>">
                <?= $i ?>
            </button>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <button onclick="loadParcours('<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>')"
                    class="px-4 py-2 rounded-lg border bg-white hover:bg-gray-100 text-sm">
                →
            </button>
        <?php endif; ?>

    </div>
<?php endif; ?>
