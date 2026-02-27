<?php
require_once __DIR__ . '/../../Core/Csrf.php';
?>

<div class="max-w-3xl mx-auto">

    <!-- TITRE -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            Ajouter un parcours
        </h1>
        <p class="text-sm text-gray-500">
            Création d'un nouveau parcours permanent Terra Aventura
        </p>
    </div>

    <!-- Bannière info Zaméla -->
    <div class="mb-6 flex items-start gap-3 bg-violet-50 border border-violet-200 rounded-xl px-4 py-3">
        <span class="text-xl mt-0.5">⚡</span>
        <div class="text-sm text-violet-800">
            <strong>Vous créez un parcours permanent.</strong>
            Pour un parcours éphémère avec une date de début et de fin, utilisez l'onglet
            <a href="/zamela" class="underline font-semibold hover:text-violet-600">Zaméla</a>.
        </div>
    </div>

    <?php
    // formulaire partagé (create / edit)
    require __DIR__ . '/form.php';
    ?>

</div>
