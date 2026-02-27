<?php
$fieldClass = 'w-full rounded-lg border border-gray-300 bg-white
               px-4 py-2.5 text-sm
               focus:border-violet-500 focus:ring-2 focus:ring-violet-100
               outline-none';
$isEdit = isset($zamela);
?>

<form method="post"
      action="<?= $isEdit ? '/zamela/update' : '/zamela/store' ?>"
      class="bg-white rounded-xl shadow-sm border p-6 space-y-6 max-w-3xl mx-auto">

    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$zamela['id'] ?>">
    <?php endif; ?>

    <!-- TITRE -->
    <div>
        <label class="block text-sm font-medium mb-1">Titre</label>
        <input type="text" name="titre" required
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($zamela['titre'] ?? '') ?>">
    </div>

    <!-- VILLE -->
    <div>
        <label class="block text-sm font-medium mb-1">Ville</label>
        <input type="text" name="ville" required
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($zamela['ville'] ?? '') ?>">
    </div>

    <!-- DATES -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-1">Date de début</label>
            <input type="date" name="date_debut" required
                   class="<?= $fieldClass ?>"
                   value="<?= htmlspecialchars($zamela['date_debut'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Date de fin</label>
            <input type="date" name="date_fin" required
                   class="<?= $fieldClass ?>"
                   value="<?= htmlspecialchars($zamela['date_fin'] ?? '') ?>">
        </div>
    </div>

    <!-- DEPARTEMENT -->
    <div>
        <label class="block text-sm font-medium mb-1">Département</label>
        <select name="departement_code" required class="<?= $fieldClass ?>">
            <?php foreach ($departements as $code => $nom): ?>
                <option value="<?= $code ?>"
                    <?= isset($zamela) && (string)$zamela['departement_code'] === (string)$code ? 'selected' : '' ?>>
                    <?= $code ?> – <?= htmlspecialchars($nom) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="departement_nom" value="">
    </div>

    <!-- NIVEAU / TERRAIN -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium mb-2">
                Niveau : <span id="niveauValue" class="font-semibold text-violet-600"><?= (int)($zamela['niveau'] ?? 3) ?></span>/5
            </label>
            <input type="range" min="1" max="5" step="1" name="niveau"
                   value="<?= (int)($zamela['niveau'] ?? 3) ?>"
                   class="w-full accent-violet-600"
                   oninput="document.getElementById('niveauValue').textContent = this.value">
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">
                Terrain : <span id="terrainValue" class="font-semibold text-violet-600"><?= (int)($zamela['terrain'] ?? 3) ?></span>/5
            </label>
            <input type="range" min="1" max="5" step="1" name="terrain"
                   value="<?= (int)($zamela['terrain'] ?? 3) ?>"
                   class="w-full accent-violet-600"
                   oninput="document.getElementById('terrainValue').textContent = this.value">
        </div>
    </div>

    <!-- DUREE -->
    <div>
        <label class="block text-sm font-medium mb-1">Durée</label>
        <input type="text" name="duree"
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($zamela['duree'] ?? '') ?>">
    </div>

    <!-- DISTANCE -->
    <div>
        <label class="block text-sm font-medium mb-1">Distance (km)</label>
        <input type="number" step="0.1" name="distance_km"
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($zamela['distance_km'] ?? '') ?>">
    </div>

    <!-- ACTIONS -->
    <div class="flex justify-between items-center pt-6 border-t">
        <a href="/zamela" class="px-4 py-2 rounded-lg border text-sm hover:bg-gray-100">
            ← Retour
        </a>
        <button class="px-6 py-2 <?= $isEdit ? 'bg-green-600 hover:bg-green-700' : 'bg-violet-600 hover:bg-violet-700' ?> text-white rounded-lg text-sm">
            <?= $isEdit ? 'Mettre à jour' : 'Créer le Zaméla' ?>
        </button>
    </div>
</form>

<script>
document.querySelector('[name="departement_code"]')?.addEventListener('change', function () {
    const label = this.options[this.selectedIndex].text;
    document.querySelector('[name="departement_nom"]').value = label.split('–')[1]?.trim() ?? '';
});
</script>
