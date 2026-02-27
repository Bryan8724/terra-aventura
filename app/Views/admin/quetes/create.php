<h1 class="text-2xl font-bold mb-6">➕ Ajouter une quête</h1>

<form method="post"
      action="/admin/quetes/store"
      id="queteForm"
      class="space-y-10">

    <!-- =======================
         QUÊTE
    ======================== -->
    <div class="bg-white rounded-lg shadow p-6 space-y-4 max-w-xl">
        <div>
            <label class="font-medium block mb-1">Nom de la quête</label>
            <input
                type="text"
                name="nom"
                required
                class="w-full border rounded px-3 py-2"
            >
        </div>

        <div>
            <label class="font-medium block mb-1">Saison (facultatif)</label>
            <input
                type="text"
                name="saison"
                class="w-full border rounded px-3 py-2"
            >
        </div>
    </div>

    <!-- =======================
         OBJETS
    ======================== -->
    <div class="space-y-4 max-w-5xl">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">🎒 Objets de la quête</h2>

            <button
                type="button"
                onclick="addObjet()"
                class="text-blue-600 text-sm font-medium"
            >
                ➕ Ajouter un objet
            </button>
        </div>

        <div id="objetsContainer" class="space-y-4"></div>
    </div>

    <!-- =======================
         ACTIONS
    ======================== -->
    <div class="flex justify-between max-w-5xl pt-6">
        <a href="/admin/quetes" class="text-gray-600 underline">
            Annuler
        </a>

        <button
            type="submit"
            id="saveBtn"
            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg"
        >
            Enregistrer la quête
        </button>
    </div>
</form>

<!-- =======================
     MODAL PARCOURS
======================== -->
<?php require __DIR__ . '/_modal-parcours.php'; ?>

<!-- =======================
     MODAL SUPPRESSION OBJET
======================== -->
<div id="deleteObjetModal"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-sm space-y-4">
        <h3 class="font-semibold text-lg">Supprimer l’objet</h3>
        <p class="text-sm text-gray-600">
            Cet objet et ses parcours seront retirés de la quête.
        </p>

        <div class="flex justify-end gap-3">
            <button type="button"
                    onclick="closeDeleteObjetModal()"
                    class="px-3 py-1 border rounded">
                Annuler
            </button>

            <button type="button"
                    id="confirmDeleteObjetBtn"
                    class="px-3 py-1 bg-red-600 text-white rounded">
                Supprimer
            </button>
        </div>
    </div>
</div>

<!-- =======================
     MODAL CONFIRMATION SAUVEGARDE
======================== -->
<div id="confirmSaveModal"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-lg space-y-4">
        <h3 class="font-semibold text-lg">
            Confirmer la création
        </h3>

        <div id="saveSummary"
             class="text-sm text-gray-700 space-y-2 max-h-64 overflow-y-auto border rounded p-3 bg-gray-50">
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <button type="button"
                    onclick="closeConfirmSaveModal()"
                    class="px-4 py-2 border rounded">
                Annuler
            </button>

            <button type="button"
                    id="confirmSaveBtn"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Confirmer et créer
            </button>
        </div>
    </div>
</div>

<script src="/js/admin-quetes.js" defer></script>
