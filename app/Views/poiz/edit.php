<?php
require_once __DIR__ . '/../../Core/Csrf.php';
?>

<h1 class="text-2xl font-bold mb-6">Modifier le POIZ</h1>

<form
    method="post"
    action="/poiz/update/<?= (int)$poiz->id ?>"
    enctype="multipart/form-data"
    class="space-y-4 max-w-lg"
    onsubmit="event.preventDefault(); teraConfirm({
        form: this,
        title: 'Modifier le POIZ',
        message: 'Les modifications seront appliquées immédiatement.'
    });"
>
    <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

    <div>
        <label class="block font-medium">Nom</label>
        <input type="text" name="name" required
               value="<?= htmlspecialchars($poiz->name) ?>"
               class="w-full border rounded px-3 py-2">
    </div>

    <div>
        <label class="block font-medium">Description</label>
        <textarea name="description" required
                  class="w-full border rounded px-3 py-2"
                  rows="4"><?= htmlspecialchars($poiz->description) ?></textarea>
    </div>

    <div>
        <label class="block font-medium">Nouveau logo (optionnel)</label>
        <input type="file" name="logo" accept="image/*">
        <p class="text-xs text-gray-500 mt-1">
            Si aucun fichier n’est sélectionné, le logo actuel sera conservé
        </p>
    </div>

    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
        Mettre à jour
    </button>
</form>
