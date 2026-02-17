<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-semibold">POIZ</h1>

    <a href="/poiz/create"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        ➕ Ajouter un POIZ
    </a>
</div>

<?php if (empty($poiz)): ?>
    <div class="bg-white rounded shadow p-6">
        <p class="text-gray-500">Aucun POIZ disponible.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($poiz as $p): ?>
            <div class="bg-white rounded shadow p-6 flex flex-col justify-between">

                <div>
                    <h2 class="text-lg font-semibold mb-2">
                        <?= htmlspecialchars($p['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </h2>

                    <?php if (!empty($p['logo'])): ?>
                        <img
                            src="<?= htmlspecialchars($p['logo'], ENT_QUOTES, 'UTF-8') ?>"
                            alt=""
                            class="h-12 mb-3"
                        >
                    <?php endif; ?>

                    <div class="text-sm text-gray-500 space-y-1">
                        <div>Thème : <?= htmlspecialchars($p['theme'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                        <div>
                            Actif :
                            <?= (($p['actif'] ?? 1) == 1) ? 'Oui' : 'Non' ?>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="mt-4 flex gap-2">
                    <a href="/poiz/edit?id=<?= (int)$p['id'] ?>"
                       class="px-3 py-1 text-sm bg-yellow-500 text-white rounded hover:bg-yellow-600">
                        ✏️ Modifier
                    </a>

                    <?php if (empty($p['utilise'])): ?>
                        <form method="post" action="/poiz/delete"
                              onsubmit="return confirm('Supprimer ce POIZ ?');">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button
                                type="submit"
                                class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                🗑️ Supprimer
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="px-3 py-1 text-sm bg-gray-300 text-gray-600 rounded cursor-not-allowed">
                            🔒 Utilisé
                        </span>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
