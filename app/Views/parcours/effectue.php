<?php
$dateValidation = $data['date_validation'] ?? null;
$dateStr = $dateValidation ? date('d/m/Y', strtotime($dateValidation)) : '—';
$heureStr = $dateValidation ? date('H:i', strtotime($dateValidation)) : '—';
?>

<div class="max-w-2xl mx-auto">

    <!-- Fil d'ariane -->
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-6">
        <a href="/parcours" class="hover:text-blue-600 transition">Parcours</a>
        <span>›</span>
        <span class="text-gray-600 font-medium truncate"><?= htmlspecialchars($data['titre']) ?></span>
    </div>

    <!-- Hero card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-5">

        <!-- Bandeau vert succès -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 px-6 py-4 flex items-center gap-3">
            <span class="text-2xl">✔</span>
            <div>
                <p class="text-white font-bold text-base leading-tight">Parcours validé</p>
                <p class="text-green-100 text-xs mt-0.5">Bravo, ce parcours est dans votre historique !</p>
            </div>
        </div>

        <!-- Contenu -->
        <div class="p-6">

            <!-- POIZ + titre -->
            <div class="flex items-center gap-4 mb-6">
                <?php if (!empty($data['poiz_logo'])): ?>
                    <img src="<?= htmlspecialchars($data['poiz_logo']) ?>"
                         alt="Logo POIZ"
                         class="w-16 h-16 object-contain bg-gray-50 border border-gray-100 rounded-xl p-1 flex-shrink-0">
                <?php else: ?>
                    <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">🗺️</div>
                <?php endif; ?>

                <div>
                    <h1 class="text-xl font-bold text-gray-800 leading-tight">
                        <?= htmlspecialchars($data['titre']) ?>
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        📍 <?= htmlspecialchars($data['ville']) ?>
                        (<?= htmlspecialchars($data['departement_code']) ?>)
                    </p>
                    <?php if (!empty($data['poiz_nom'])): ?>
                        <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($data['poiz_nom']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats de validation -->
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-gray-50 rounded-xl p-4 text-center border border-gray-100">
                    <p class="text-xs text-gray-400 mb-1 uppercase tracking-wide font-medium">Date</p>
                    <p class="text-sm font-bold text-gray-800"><?= $dateStr ?></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 text-center border border-gray-100">
                    <p class="text-xs text-gray-400 mb-1 uppercase tracking-wide font-medium">Heure</p>
                    <p class="text-sm font-bold text-gray-800"><?= $heureStr ?></p>
                </div>
                <div class="bg-emerald-50 rounded-xl p-4 text-center border border-emerald-100">
                    <p class="text-xs text-emerald-600 mb-1 uppercase tracking-wide font-medium">Badges</p>
                    <p class="text-xl font-extrabold text-emerald-600"><?= (int)$data['badges_recuperes'] ?></p>
                </div>
            </div>

        </div>
    </div>

    <!-- Objet de quête lié -->
    <?php if (!empty($objet)): ?>
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-5 flex items-start gap-3">
            <span class="text-xl flex-shrink-0">🎯</span>
            <div>
                <p class="text-sm font-semibold text-blue-800">Objet de quête lié</p>
                <p class="text-sm text-blue-700 mt-0.5"><?= htmlspecialchars($objet['nom']) ?></p>
                <?php if ($dernierParcours): ?>
                    <p class="text-xs text-amber-600 mt-1.5 font-medium">
                        ⚠️ Annuler retirera cet objet de vos quêtes obtenues
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <a href="/parcours"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            ← Retour
        </a>
        <button id="btn-reset-parcours"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-red-50 border border-red-200 text-sm font-medium text-red-600 hover:bg-red-100 transition">
            ✕ Annuler la validation
        </button>
    </div>

</div>

<!-- MODAL RESET -->
<div id="modal-reset"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Annuler la validation</h2>
            <button onclick="closeModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 text-xl">×</button>
        </div>

        <?php if (!empty($objet)): ?>
            <div class="bg-blue-50 rounded-xl p-4 mb-4 text-sm">
                <p class="text-gray-600">Objet de quête lié :</p>
                <p class="font-semibold text-blue-700 mt-1"><?= htmlspecialchars($objet['nom']) ?></p>
            </div>

            <?php if ($dernierParcours): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 text-sm text-red-700">
                    <p class="font-semibold mb-1">⚠️ Action irréversible</p>
                    <p>Ce parcours est le dernier lié à cet objet. En confirmant, l'objet sera retiré de vos quêtes obtenues.</p>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-600 mb-5">
                    Un autre parcours lié à cet objet est encore effectué. L'objet restera obtenu.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-sm text-gray-600 mb-5">Confirmez-vous l'annulation de la validation de ce parcours ?</p>
        <?php endif; ?>

        <div class="flex justify-end gap-3">
            <button onclick="closeModal()"
                    class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm hover:bg-gray-50 transition">
                Annuler
            </button>
            <button onclick="resetParcours()"
                    class="px-5 py-2.5 <?= ($dernierParcours ?? false) ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' ?> text-white rounded-xl text-sm font-medium transition">
                Confirmer
            </button>
        </div>
    </div>
</div>

<script>
const btnReset = document.getElementById('btn-reset-parcours');
const modal    = document.getElementById('modal-reset');

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

modal?.addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function resetParcours() {
    fetch('/parcours/reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ parcours_id: <?= (int)$_GET['id'] ?> })
    })
    .then(() => window.location.href = '/parcours')
    .catch(() => alert("Erreur lors de l'annulation"));
}
</script>
