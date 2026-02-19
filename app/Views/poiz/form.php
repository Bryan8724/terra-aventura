<form
    method="post"
    enctype="multipart/form-data"
    action="<?= isset($poiz) ? '/poiz/update' : '/poiz/store' ?>"
    class="bg-white rounded shadow p-6 max-w-xl"
>

    <?php if (isset($poiz)): ?>
        <input type="hidden" name="id" value="<?= (int)$poiz['id'] ?>">
    <?php endif; ?>

    <!-- NOM -->
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Nom</label>
        <input
            type="text"
            name="nom"
            value="<?= htmlspecialchars($poiz['nom'] ?? '') ?>"
            class="w-full border rounded px-3 py-2"
            required
        >
    </div>

    <!-- THEME -->
    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Thème</label>
        <input
            type="text"
            name="theme"
            value="<?= htmlspecialchars($poiz['theme'] ?? '') ?>"
            class="w-full border rounded px-3 py-2"
        >
    </div>

    <!-- LOGO -->
    <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Logo du POIZ</label>

        <?php if (!empty($poiz['logo'])): ?>
            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-1">Logo actuel :</p>
                <img
                    src="<?= htmlspecialchars($poiz['logo']) ?>"
                    alt="Logo POIZ"
                    class="h-16"
                    id="logo-preview"
                >
            </div>
        <?php else: ?>
            <img id="logo-preview" class="h-16 mb-3 hidden">
        <?php endif; ?>

        <input
            type="file"
            name="logo"
            accept="image/*"
            <?= isset($poiz) ? '' : 'required' ?>
            onchange="previewLogo(event)"
        >
    </div>

    <!-- ACTIONS -->
    <div class="flex gap-2">
        <button
            type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
            <?= isset($poiz) ? 'Mettre à jour' : 'Créer le POIZ' ?>
        </button>

        <a href="/poiz" class="px-4 py-2 rounded border">
            Annuler
        </a>
    </div>
</form>

<script>
function previewLogo(event) {
    const img = document.getElementById('logo-preview');
    img.src = URL.createObjectURL(event.target.files[0]);
    img.classList.remove('hidden');
}
</script>
