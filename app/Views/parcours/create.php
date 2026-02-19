<?php
require_once __DIR__ . '/../../Core/Csrf.php';
?>

<div class="max-w-3xl mx-auto">

    <!-- TITRE -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">
            Ajouter un parcours
        </h1>
        <p class="text-sm text-gray-500">
            Création d’un nouveau parcours Terra Aventura
        </p>
    </div>

    <?php
    // formulaire partagé (create / edit)
    require __DIR__ . '/form.php';
    ?>

</div>
