<?php
// $parcours est déjà fourni par le contrôleur
?>

<div class="max-w-3xl mx-auto">

    <!-- TITRE -->
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">
            Modifier le parcours
        </h1>
        <p class="text-sm text-gray-500">
            Mise à jour des informations du parcours
        </p>
    </div>

    <?php
    // formulaire partagé (create / edit)
    require __DIR__ . '/form.php';
    ?>

</div>
