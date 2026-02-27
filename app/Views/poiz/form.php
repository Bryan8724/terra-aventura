<?php
$isEdit     = isset($poiz);
$fieldClass = 'w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800
               focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:bg-white outline-none transition';
?>

<div class="max-w-lg mx-auto">

    <!-- Fil d'ariane -->
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <a href="/poiz" class="hover:text-indigo-600 transition">POIZ</a>
        <span>›</span>
        <span class="text-gray-600 font-medium"><?= $isEdit ? htmlspecialchars($poiz['nom']) : 'Nouveau POIZ' ?></span>
    </div>

    <!-- Titre -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <?= $isEdit ? '✏️ Modifier le POIZ' : '➕ Ajouter un POIZ' ?>
        </h1>
        <p class="text-sm text-gray-400 mt-1">
            <?= $isEdit ? 'Mettez à jour les informations du personnage' : 'Créez un nouveau personnage Terra Aventura' ?>
        </p>
    </div>

    <form method="post"
          enctype="multipart/form-data"
          action="<?= $isEdit ? '/poiz/update' : '/poiz/store' ?>"
          class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">

        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$poiz['id'] ?>">
        <?php endif; ?>

        <!-- NOM -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                Nom <span class="text-red-400">*</span>
            </label>
            <input type="text" name="nom" required
                   class="<?= $fieldClass ?>"
                   value="<?= htmlspecialchars($poiz['nom'] ?? '') ?>"
                   placeholder="ex: Ziclou">
        </div>

        <!-- THÈME -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Thème</label>
            <input type="text" name="theme"
                   class="<?= $fieldClass ?>"
                   value="<?= htmlspecialchars($poiz['theme'] ?? '') ?>"
                   placeholder="ex: À Bicyclette, Animaux…">
        </div>

        <!-- LOGO -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Logo <?= $isEdit ? '<span class="text-gray-400 font-normal">(laisser vide pour conserver)</span>' : '<span class="text-red-400">*</span>' ?>
            </label>

            <!-- Prévisualisation -->
            <div id="logoPreviewWrap" class="mb-3 flex items-center gap-4 p-3 bg-gray-50 rounded-xl border border-gray-100 <?= empty($poiz['logo']) ? 'hidden' : '' ?>">
                <img id="logoPreview"
                     src="<?= htmlspecialchars($poiz['logo'] ?? '') ?>"
                     alt="Aperçu"
                     class="w-16 h-16 object-contain bg-white rounded-lg border border-gray-100 p-1">
                <div>
                    <p class="text-xs font-semibold text-gray-600">Aperçu du logo</p>
                    <p class="text-xs text-gray-400 mt-0.5" id="logoFileName">
                        <?= $isEdit && !empty($poiz['logo']) ? basename($poiz['logo']) : '' ?>
                    </p>
                </div>
            </div>

            <!-- Input file stylé -->
            <label for="logoInput"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition">
                <span class="text-xl">📁</span>
                <div>
                    <p class="text-sm font-medium text-gray-700">Choisir un fichier</p>
                    <p class="text-xs text-gray-400">PNG, JPG, WEBP</p>
                </div>
            </label>
            <input type="file" id="logoInput" name="logo" accept="image/*"
                   <?= $isEdit ? '' : 'required' ?>
                   class="hidden"
                   onchange="previewLogo(event)">
        </div>

        <!-- ACTIONS -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <a href="/poiz"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition">
                ← Retour
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl
                           <?= $isEdit ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700' ?>
                           text-white text-sm font-semibold shadow-sm transition">
                <?= $isEdit ? '✔ Mettre à jour' : '➕ Créer le POIZ' ?>
            </button>
        </div>

    </form>
</div>

<script>
function previewLogo(event) {
    const file = event.target.files[0];
    if (!file) return;
    const img  = document.getElementById('logoPreview');
    const wrap = document.getElementById('logoPreviewWrap');
    const name = document.getElementById('logoFileName');
    img.src = URL.createObjectURL(file);
    name.textContent = file.name;
    wrap.classList.remove('hidden');
}
</script>
