<?php
$isAdmin = (($_SESSION['user']['role'] ?? '') === 'admin');
$quetes  = is_array($quetes ?? null) ? $quetes : [];
?>

<h1 class="text-2xl font-bold mb-4 flex items-center gap-2">
    🎯 Quêtes
</h1>

<div class="flex justify-between items-center mb-6">
    <p class="text-gray-500">
        Suivi des quêtes et des objets obtenus
    </p>

    <?php if ($isAdmin): ?>
        <a href="/admin/quetes/create"
           class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow">
            ➕ Ajouter une quête
        </a>
    <?php endif; ?>
</div>

<?php if (empty($quetes)): ?>
    <div class="bg-white p-8 rounded-xl shadow text-center text-gray-500">
        Aucune quête disponible.
    </div>
<?php else: ?>

<div class="space-y-8">

<?php foreach ($quetes as $quete): ?>
<?php if (empty($quete['id'])) continue; ?>

<div class="bg-white shadow rounded-2xl p-6 space-y-6 border">

    <div class="flex justify-between items-start">
        <div class="space-y-2">
            <h2 class="text-xl font-semibold text-gray-800">
                <?= htmlspecialchars((string)($quete['nom'] ?? '')) ?>
            </h2>

            <?php if (!empty($quete['saison'])): ?>
                <span class="inline-flex items-center gap-1 text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-medium">
                    🗓️ Saison <?= htmlspecialchars((string)$quete['saison']) ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($isAdmin): ?>
            <div class="flex gap-3 text-lg">
                <a href="/admin/quetes/edit?id=<?= (int)$quete['id'] ?>"
                   class="text-blue-600 hover:text-blue-800">✏️</a>

                <button type="button"
                        data-delete-quete="<?= (int)$quete['id'] ?>"
                        data-quete-nom="<?= htmlspecialchars((string)$quete['nom'], ENT_QUOTES) ?>"
                        class="text-red-600 hover:text-red-800">🗑</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- ================= OBJETS ================= -->
    <div class="space-y-4">

    <?php foreach (($quete['objets'] ?? []) as $objet): ?>
    <?php
        $parcours = $objet['parcours'] ?? [];
        $total    = count($parcours);
        $done     = array_filter($parcours, fn($p) => !empty($p['obtenu']));
        $isDone   = ($total > 0 && count($done) === $total);
    ?>

        <div class="ml-4 bg-gray-50 rounded-xl p-4 space-y-3 border">

            <button type="button"
                    onclick="toggleObjet(<?= (int)$objet['id'] ?>)"
                    class="w-full flex justify-between items-center text-left">

                <div class="flex items-center gap-2 font-medium text-gray-800">
                    🎒 <?= htmlspecialchars((string)($objet['nom'] ?? '')) ?>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span class="<?= $isDone ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?> px-3 py-1 rounded-full font-medium">
                        <?= $isDone ? '✔ Obtenu' : '⏳ En cours' ?>
                    </span>

                    <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-medium">
                        <?= count($done) ?>/<?= $total ?> parcours
                    </span>

                    <span id="chevron-<?= (int)$objet['id'] ?>"
                          class="transition-transform duration-300">▶</span>
                </div>
            </button>

            <div id="parcours-<?= (int)$objet['id'] ?>"
                 class="grid md:grid-cols-2 gap-3 mt-3 max-h-0 opacity-0 overflow-hidden transition-all duration-300">

                <?php foreach ($parcours as $p): ?>
                    <div class="flex items-center gap-3 bg-white rounded-xl p-3 shadow-sm border">
                        <?php if (!empty($p['logo'])): ?>
                            <img src="<?= htmlspecialchars((string)$p['logo']) ?>" class="w-10 h-10 object-contain">
                        <?php endif; ?>

                        <div class="flex-1 text-sm">
                            <div class="font-medium">
                                <?= htmlspecialchars((string)($p['nom'] ?? '')) ?>
                                <?php if (!empty($p['obtenu'])): ?>
                                    <span class="text-green-600 text-xs">✔</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?= htmlspecialchars((string)($p['ville'] ?? '')) ?>
                                (<?= htmlspecialchars((string)($p['dep'] ?? '')) ?>)
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>

    <?php endforeach; ?>

    </div>
</div>

<?php endforeach; ?>

</div>
<?php endif; ?>

<script>
function toggleObjet(id) {
    const box = document.getElementById('parcours-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const open = box.classList.contains('max-h-[500px]');
    box.classList.toggle('max-h-[500px]', !open);
    box.classList.toggle('opacity-100', !open);
    box.classList.toggle('max-h-0', open);
    box.classList.toggle('opacity-0', open);
    chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(90deg)';
}
</script>
