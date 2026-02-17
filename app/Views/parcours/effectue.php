<div class="max-w-2xl mx-auto">

    <!-- TITRE -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold mb-1">
            <?= htmlspecialchars($data['titre']) ?>
        </h1>
        <p class="text-gray-600">
            <?= htmlspecialchars($data['ville']) ?>
            (<?= htmlspecialchars($data['departement_code']) ?>)
        </p>
    </div>

    <!-- CARTE -->
    <div class="bg-white shadow rounded-lg p-6">

        <!-- POIZ -->
        <div class="flex items-center gap-4 mb-6">
            <?php if (!empty($data['poiz_logo'])): ?>
                <img
                    src="<?= htmlspecialchars($data['poiz_logo']) ?>"
                    alt="Logo poiz"
                    class="h-20 w-auto"
                >
            <?php endif; ?>

            <div>
                <div class="text-sm text-gray-500">Poiz</div>
                <div class="text-lg font-medium">
                    <?= htmlspecialchars($data['poiz_nom']) ?>
                </div>
            </div>
        </div>

        <!-- INFOS VALIDATION -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">

            <div class="bg-gray-50 rounded p-4">
                <div class="text-gray-500">Date de validation</div>
                <div class="font-medium">
                    <?= date('d/m/Y', strtotime($data['date_validation'])) ?>
                </div>
            </div>

            <div class="bg-gray-50 rounded p-4">
                <div class="text-gray-500">Heure</div>
                <div class="font-medium">
                    <?= date('H:i', strtotime($data['date_validation'])) ?>
                </div>
            </div>

            <div class="bg-gray-50 rounded p-4 sm:col-span-2">
                <div class="text-gray-500">Badges récupérés</div>
                <div class="text-xl font-semibold text-green-600">
                    <?= (int)$data['badges_recuperes'] ?>
                </div>
            </div>

        </div>

        <!-- ACTIONS -->
        <div class="mt-8 flex justify-between items-center">

            <a href="/parcours"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                ← Retour aux parcours
            </a>

            <button
                id="btn-reset-parcours"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                ❌ Annuler la validation
            </button>

        </div>

    </div>

</div>

<?php if (!empty($objet)): ?>
<!-- MODAL RESET -->
<div id="modal-reset"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6">

        <h2 class="text-xl font-semibold mb-4">
            Annuler la validation
        </h2>

        <p class="text-gray-700 mb-2">
            Ce parcours est lié à l’objet de quête :
        </p>

        <p class="font-semibold text-blue-600 mb-4">
            <?= htmlspecialchars($objet['nom']) ?>
        </p>

        <?php if ($dernierParcours): ?>
            <p class="text-red-600 font-semibold mb-6">
                ⚠️ Attention : ce parcours est le dernier permettant
                d’obtenir cet objet.<br>
                En continuant, l’objet sera retiré de vos quêtes obtenues.
            </p>
        <?php else: ?>
            <p class="text-gray-700 mb-6">
                Un autre parcours lié à cet objet est encore effectué.<br>
                L’objet restera obtenu.
            </p>
        <?php endif; ?>

        <div class="flex justify-end gap-3">
            <button
                onclick="closeModal()"
                class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">
                Annuler
            </button>

            <button
                onclick="resetParcours()"
                class="px-4 py-2 <?= $dernierParcours ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' ?> text-white rounded">
                Confirmer
            </button>
        </div>

    </div>
</div>
<?php endif; ?>

<script>
const btnReset = document.getElementById('btn-reset-parcours');
const modal = document.getElementById('modal-reset');

btnReset.addEventListener('click', () => {
    <?php if (!empty($objet)): ?>
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    <?php else: ?>
        resetParcours();
    <?php endif; ?>
});

function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function resetParcours() {
    fetch('/parcours/reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            parcours_id: <?= (int)$_GET['id'] ?>
        })
    })
    .then(() => window.location.href = '/parcours')
    .catch(() => alert('Erreur lors de l’annulation'));
}
</script>
