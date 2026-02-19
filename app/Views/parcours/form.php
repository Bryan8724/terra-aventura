<?php
$fieldClass = 'w-full rounded-lg border border-gray-300 bg-white
               px-4 py-2.5 text-sm
               focus:border-blue-500 focus:ring-2 focus:ring-blue-100
               outline-none';

$isEdit = isset($parcours);
?>

<form method="post"
      action="<?= $isEdit ? '/parcours/update' : '/parcours/store' ?>"
      class="bg-white rounded-xl shadow-sm border p-6 space-y-6 max-w-3xl mx-auto">

    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$parcours['id'] ?>">
    <?php endif; ?>

    <!-- TITRE -->
    <div>
        <label class="block text-sm font-medium mb-1">Titre</label>
        <input type="text"
               name="titre"
               required
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($parcours['titre'] ?? '') ?>">
    </div>

    <!-- VILLE -->
    <div>
        <label class="block text-sm font-medium mb-1">Ville</label>
        <input type="text"
               name="ville"
               required
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($parcours['ville'] ?? '') ?>">
    </div>

    <!-- POIZ + PREVIEW -->
    <div>
        <label class="block text-sm font-medium mb-1">POIZ</label>

        <select name="poiz_id"
                id="poizSelect"
                required
                class="<?= $fieldClass ?>">
            <?php foreach ($poiz as $z): ?>
                <option value="<?= (int)$z['id'] ?>"
                        data-nom="<?= htmlspecialchars($z['nom']) ?>"
                        data-logo="<?= htmlspecialchars($z['logo'] ?? '') ?>"
                    <?= isset($parcours) && (int)$parcours['poiz_id'] === (int)$z['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($z['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- PREVIEW -->
        <div id="poizPreview"
             class="mt-3 flex items-center gap-4 rounded-lg border bg-gray-50 p-3 hidden">

            <img id="poizPreviewImg"
                 src=""
                 alt="Logo POIZ"
                 class="w-16 h-16 object-contain rounded bg-white border hidden">

            <div>
                <div id="poizPreviewName"
                     class="font-medium text-gray-800"></div>
                <div class="text-xs text-gray-500">POIZ sélectionné</div>
            </div>
        </div>
    </div>

    <!-- DEPARTEMENT -->
    <div>
        <label class="block text-sm font-medium mb-1">Département</label>
        <select name="departement_code" required class="<?= $fieldClass ?>">
            <?php foreach ($departements as $code => $nom): ?>
                <option value="<?= $code ?>"
                    <?= isset($parcours) && (string)$parcours['departement_code'] === (string)$code ? 'selected' : '' ?>>
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
                Niveau :
                <span id="niveauValue" class="font-semibold text-blue-600">
                    <?= (int)($parcours['niveau'] ?? 3) ?>
                </span>/5
            </label>

            <input type="range"
                   min="1" max="5" step="1"
                   name="niveau"
                   value="<?= (int)($parcours['niveau'] ?? 3) ?>"
                   class="w-full accent-blue-600"
                   oninput="document.getElementById('niveauValue').textContent = this.value">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">
                Terrain :
                <span id="terrainValue" class="font-semibold text-blue-600">
                    <?= (int)($parcours['terrain'] ?? 3) ?>
                </span>/5
            </label>

            <input type="range"
                   min="1" max="5" step="1"
                   name="terrain"
                   value="<?= (int)($parcours['terrain'] ?? 3) ?>"
                   class="w-full accent-blue-600"
                   oninput="document.getElementById('terrainValue').textContent = this.value">
        </div>

    </div>

    <!-- DUREE -->
    <div>
        <label class="block text-sm font-medium mb-1">Durée</label>
        <input type="text"
               name="duree"
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($parcours['duree'] ?? '') ?>">
    </div>

    <!-- DISTANCE -->
    <div>
        <label class="block text-sm font-medium mb-1">Distance (km)</label>
        <input type="number"
               step="0.1"
               name="distance_km"
               class="<?= $fieldClass ?>"
               value="<?= htmlspecialchars($parcours['distance_km'] ?? '') ?>">
    </div>

    <!-- ACTIONS -->
    <div class="flex justify-between items-center pt-6 border-t">
        <a href="/parcours"
           class="px-4 py-2 rounded-lg border text-sm hover:bg-gray-100">
            ← Retour
        </a>

        <button class="px-6 py-2 <?= $isEdit ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' ?> text-white rounded-lg text-sm">
            <?= $isEdit ? 'Mettre à jour' : 'Créer le parcours' ?>
        </button>
    </div>
</form>

<script>
/* Département */
document.querySelector('[name="departement_code"]')?.addEventListener('change', function () {
    const label = this.options[this.selectedIndex].text;
    document.querySelector('[name="departement_nom"]').value =
        label.split('–')[1]?.trim() ?? '';
});

/* POIZ preview */
const select = document.getElementById('poizSelect');
const preview = document.getElementById('poizPreview');
const img = document.getElementById('poizPreviewImg');
const nameEl = document.getElementById('poizPreviewName');

function updatePoizPreview() {
    if (!select || !select.options.length) return;

    const opt = select.options[select.selectedIndex];
    nameEl.textContent = opt.dataset.nom ?? '';
    preview.classList.remove('hidden');

    if (opt.dataset.logo) {
        img.src = opt.dataset.logo;
        img.classList.remove('hidden');
    } else {
        img.classList.add('hidden');
    }
}

select?.addEventListener('change', updatePoizPreview);
updatePoizPreview(); // create + edit
</script>
