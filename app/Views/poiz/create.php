<?php
require_once __DIR__ . '/../../Core/Csrf.php';
?>

<h1 class="text-2xl font-bold mb-6">Ajouter un POIZ</h1>

<form method="post" action="/poiz/store" enctype="multipart/form-data" class="space-y-4">

    <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

    <div>
        <label class="block font-medium">Nom</label>
        <input type="text" name="name" required class="w-full border rounded px-3 py-2">
    </div>

    <div>
        <label class="block font-medium">Description</label>
        <textarea name="description" required class="w-full border rounded px-3 py-2"></textarea>
    </div>

    <div>
        <label class="block font-medium">Logo</label>
        <input type="file" name="logo" accept="image/*" required>
    </div>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Enregistrer
    </button>
</form>
