<div id="parcoursModal"
     class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

    <div class="bg-white p-6 rounded-xl w-full max-w-lg space-y-4 shadow-lg">
        <div>
            <h3 class="text-lg font-bold">
                ➕ Ajouter un parcours
            </h3>
            <p class="text-sm text-gray-500">
                Recherchez un parcours à associer à cet objet
            </p>
        </div>

        <input type="text"
               id="searchParcours"
               placeholder="Rechercher par nom de parcours…"
               autocomplete="off"
               class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">

        <div id="parcoursResults"
             class="space-y-2 text-sm max-h-64 overflow-y-auto border rounded p-2 bg-gray-50">
            <!-- Résultats injectés en JS -->
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button"
                    onclick="closeParcoursModal()"
                    class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">
                Fermer
            </button>
        </div>
    </div>
</div>
