<?php ob_start(); ?>

<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">

    <!-- Parcours -->
    <a href="/parcours"
       class="bg-white p-6 rounded shadow hover:shadow-lg transition block">
        <p class="text-gray-500">Parcours</p>
        <p class="text-3xl font-bold">🗺️</p>
        <p class="text-sm text-gray-400 mt-2">
            Gérer les parcours
        </p>
    </a>

    <!-- POIZ -->
    <a href="/parcours"
       class="bg-white p-6 rounded shadow hover:shadow-lg transition block">
        <p class="text-gray-500">POIZ</p>
        <p class="text-3xl font-bold">📍</p>
        <p class="text-sm text-gray-400 mt-2">
            Via les parcours
        </p>
    </a>

    <!-- 🔥 Quêtes -->
    <a href="/quetes"
       class="bg-white p-6 rounded shadow hover:shadow-lg transition block border-l-4
              <?= !empty($_SESSION['quetes_a_confirmer'] ?? []) ? 'border-orange-500' : 'border-blue-600' ?>">

        <div class="flex justify-between items-start">
            <div>
                <p class="text-gray-500">Quêtes</p>
                <p class="text-3xl font-bold">🎯</p>
            </div>

            <?php if (!empty($_SESSION['quetes_a_confirmer'] ?? [])): ?>
                <span class="text-xs bg-orange-500 text-white px-2 py-1 rounded-full animate-pulse">
                    Action requise
                </span>
            <?php endif; ?>
        </div>

        <p class="text-sm text-gray-400 mt-2">
            Suivre l’avancement des quêtes
        </p>
    </a>

    <!-- Événements -->
    <div class="bg-white p-6 rounded shadow opacity-50 cursor-not-allowed">
        <p class="text-gray-500">Événements</p>
        <p class="text-3xl font-bold">🎉</p>
        <p class="text-sm text-gray-400 mt-2">
            Bientôt disponible
        </p>
    </div>

</div>

<?php
$content = ob_get_clean();
$title = 'Dashboard';
require VIEW_PATH . '/partials/layout.php';
