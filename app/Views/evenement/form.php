<?php
$fc = 'w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none';
$isEdit = isset($evt);
$existingParcours = $isEdit ? ($evt['parcours'] ?? []) : [];
?>
<div class="max-w-3xl mx-auto space-y-6">

<form method="post"
      action="<?= $isEdit ? '/evenement/update' : '/evenement/store' ?>"
      enctype="multipart/form-data"
      class="bg-white rounded-xl shadow-sm border p-6 space-y-6">

    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)$evt['id'] ?>">
    <?php endif; ?>

    <!-- NOM -->
    <div>
        <label class="block text-sm font-medium mb-1">Nom de l'événement</label>
        <input type="text" name="nom" required class="<?= $fc ?>"
               value="<?= htmlspecialchars($evt['nom'] ?? '') ?>">
    </div>

    <!-- DATES -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Date de début</label>
            <input type="date" name="date_debut" required class="<?= $fc ?>"
                   value="<?= htmlspecialchars($evt['date_debut'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Date de fin</label>
            <input type="date" name="date_fin" required class="<?= $fc ?>"
                   value="<?= htmlspecialchars($evt['date_fin'] ?? '') ?>">
        </div>
    </div>

    <!-- VILLE + DÉPARTEMENT -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Ville</label>
            <input type="text" name="ville" required class="<?= $fc ?>"
                   value="<?= htmlspecialchars($evt['ville'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Département</label>
            <select name="departement_code" required class="<?= $fc ?>">
                <?php foreach ($departements as $code => $nom): ?>
                    <option value="<?= $code ?>"
                        <?= isset($evt) && (string)$evt['departement_code'] === (string)$code ? 'selected' : '' ?>>
                        <?= $code ?> – <?= htmlspecialchars($nom) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- IMAGE -->
    <div>
        <label class="block text-sm font-medium mb-1">
            Image de l'événement <span class="text-gray-400 font-normal">(optionnel)</span>
        </label>
        <?php if ($isEdit && !empty($evt['image'])): ?>
            <div class="mb-2 flex items-center gap-3">
                <img src="<?= htmlspecialchars($evt['image']) ?>" class="h-16 w-24 object-cover rounded border" alt="">
                <span class="text-xs text-gray-400">Image actuelle — choisir un nouveau fichier pour remplacer</span>
            </div>
        <?php endif; ?>
        <input type="file" name="image" accept="image/*" class="block w-full text-sm text-gray-500
               file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
               file:text-sm file:font-medium file:bg-orange-50 file:text-orange-700
               hover:file:bg-orange-100">
    </div>

    <!-- PARCOURS JSON (hidden) -->
    <input type="hidden" name="parcours_json" id="parcoursJsonInput" value="<?= htmlspecialchars(json_encode($existingParcours)) ?>">

    <!-- ACTIONS -->
    <div class="flex justify-between items-center pt-4 border-t">
        <a href="/evenement" class="px-4 py-2 rounded-lg border text-sm hover:bg-gray-100">← Retour</a>
        <button type="submit"
                class="px-6 py-2 <?= $isEdit ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-500 hover:bg-orange-600' ?> text-white rounded-lg text-sm">
            <?= $isEdit ? 'Mettre à jour' : 'Créer l\'événement' ?>
        </button>
    </div>
</form>

<!-- SECTION PARCOURS -->
<div class="bg-white rounded-xl shadow-sm border p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-gray-800">🗺️ Parcours de l'événement</h2>
        <button type="button" id="addParcoursBtn"
                class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 text-orange-700 border border-orange-200 rounded-lg text-sm font-medium hover:bg-orange-100 transition">
            ➕ Ajouter un parcours
        </button>
    </div>
    <p class="text-xs text-gray-400 mb-4">
        Ces parcours sont propres à cet événement. La ville et le département sont ceux de l'événement.
    </p>

    <div id="parcoursListEl" class="space-y-3"></div>

    <p id="noParcoursMsg" class="text-sm text-gray-400 text-center py-6 hidden">Aucun parcours ajouté</p>
</div>

</div>

<script>
const fieldCls = 'w-full rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:border-orange-400';
let parcoursList = <?= json_encode($existingParcours) ?>;

function saveParcours() {
    document.getElementById('parcoursJsonInput').value = JSON.stringify(parcoursList);
}

function renderParcours() {
    const el = document.getElementById('parcoursListEl');
    const msg = document.getElementById('noParcoursMsg');
    el.innerHTML = '';

    if (parcoursList.length === 0) { msg.classList.remove('hidden'); return; }
    msg.classList.add('hidden');

    parcoursList.forEach((p, i) => {
        const div = document.createElement('div');
        div.className = 'border border-gray-100 rounded-xl p-4 bg-gray-50 space-y-3';
        div.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Parcours ${i+1}</span>
                <button type="button" onclick="removeP(${i})"
                        class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 text-sm">🗑</button>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Titre *</label>
                <input type="text" class="${fieldCls}" value="${escHtml(p.titre||'')}" placeholder="Titre du parcours"
                       oninput="updateP(${i},'titre',this.value)" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Niveau: <span id="niv${i}">${p.niveau||3}</span>/5</label>
                    <input type="range" min="1" max="5" value="${p.niveau||3}" class="w-full accent-orange-500"
                           oninput="updateP(${i},'niveau',+this.value);document.getElementById('niv${i}').textContent=this.value">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Terrain: <span id="ter${i}">${p.terrain||3}</span>/5</label>
                    <input type="range" min="1" max="5" value="${p.terrain||3}" class="w-full accent-orange-500"
                           oninput="updateP(${i},'terrain',+this.value);document.getElementById('ter${i}').textContent=this.value">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Durée</label>
                    <input type="text" class="${fieldCls}" value="${escHtml(p.duree||'')}" placeholder="ex: 2h"
                           oninput="updateP(${i},'duree',this.value)">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Distance (km)</label>
                    <input type="number" step="0.1" class="${fieldCls}" value="${p.distance_km||''}" placeholder="0.0"
                           oninput="updateP(${i},'distance_km',+this.value)">
                </div>
            </div>`;
        el.appendChild(div);
    });
}

function escHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function updateP(i, key, val) { parcoursList[i][key] = val; saveParcours(); }
function removeP(i) { parcoursList.splice(i,1); renderParcours(); saveParcours(); }

document.getElementById('addParcoursBtn').addEventListener('click', () => {
    parcoursList.push({id: null, titre:'', niveau:3, terrain:3, duree:'', distance_km:''});
    renderParcours(); saveParcours();
});

renderParcours();
</script>
