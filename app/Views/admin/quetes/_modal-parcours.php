<!-- ══════════════════════════════════════════
     MODAL — Recherche parcours
     Appelé via openParcoursModal(objetIndex)
═══════════════════════════════════════════ -->
<div id="parcoursModal"
     class="fixed inset-0 z-50"
     style="display:none;background:rgba(0,0,0,.55);align-items:center;justify-content:center">

    <div class="modal-box" style="max-width:520px">

        <!-- Header -->
        <div class="modal-header">
            <div>
                <h3 class="text-base font-bold text-slate-800">🗺️ Ajouter un parcours</h3>
                <p class="text-xs text-slate-400 mt-0.5">Associez un parcours à cet objet de quête</p>
            </div>
            <button type="button" onclick="closeParcoursModal()" class="modal-close-btn">✕</button>
        </div>

        <!-- Recherche -->
        <div class="modal-body">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-sm pointer-events-none">🔍</span>
                <input type="text"
                       id="searchParcours"
                       placeholder="Rechercher par nom de parcours…"
                       autocomplete="off"
                       class="modal-search-input">
            </div>

            <div id="parcoursResults" class="parcours-results-container">
                <div class="parcours-empty">🔍 Commencez à taper pour rechercher…</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button type="button"
                    onclick="closeParcoursModal()"
                    class="btn-secondary">
                Fermer
            </button>
        </div>
    </div>
</div>
