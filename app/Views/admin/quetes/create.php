<?php require __DIR__ . '/_form-styles.php'; ?>

<!-- ── Breadcrumb ── -->
<div class="flex items-center gap-2 text-sm text-slate-400 mb-6">
    <a href="/admin/quetes" class="hover:text-indigo-600 transition">🎯 Quêtes</a>
    <span>›</span>
    <span class="text-slate-700 font-medium">Ajouter une quête</span>
</div>

<!-- ── Titre ── -->
<div class="flex items-center gap-3 mb-7">
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600
                flex items-center justify-center text-white text-lg shadow-md">
        ➕
    </div>
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Ajouter une quête</h1>
        <p class="text-sm text-slate-400">Créez une nouvelle quête avec ses objets et parcours</p>
    </div>
</div>

<form method="post"
      action="/admin/quetes/store"
      id="queteForm"
      class="space-y-5 max-w-3xl">

    <!-- ══════════ SECTION 1 — Infos quête ══════════ -->
    <div class="form-section">
        <div class="form-section-header">
            <span class="text-base">🎯</span>
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Informations de la quête</h2>
        </div>
        <div class="form-section-body grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label" for="nom">Nom de la quête <span class="text-red-400">*</span></label>
                <input type="text"
                       id="nom"
                       name="nom"
                       required
                       placeholder="Ex : La Quête du Dragon"
                       class="field-input"
                       oninput="document.getElementById('quetePreview').textContent = this.value || 'Nouvelle quête'">
            </div>
            <div>
                <label class="field-label" for="saison">Saison</label>
                <input type="text"
                       id="saison"
                       name="saison"
                       placeholder="Ex : 2024 — facultatif"
                       class="field-input">
                <p class="field-hint">Laissez vide si la quête n'est pas liée à une saison.</p>
            </div>
        </div>

        <!-- Aperçu live -->
        <div class="mx-5 mb-5 px-4 py-2.5 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center gap-2">
            <span class="text-indigo-400 text-xs font-semibold uppercase tracking-wide">Aperçu :</span>
            <span id="quetePreview" class="text-sm font-semibold text-indigo-700">Nouvelle quête</span>
        </div>
    </div>

    <!-- ══════════ SECTION 2 — Objets ══════════ -->
    <div class="form-section">
        <div class="form-section-header justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base">🎒</span>
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Objets de la quête</h2>
            </div>
            <button type="button"
                    onclick="addObjet()"
                    class="btn-add-objet">
                ➕ Ajouter un objet
            </button>
        </div>

        <!-- Placeholder vide -->
        <div id="objetsEmpty"
             class="m-4 rounded-xl border-2 border-dashed border-slate-200 p-8 text-center text-slate-400 text-sm">
            <p class="text-2xl mb-2">🎒</p>
            <p>Aucun objet pour l'instant.</p>
            <p class="text-xs mt-1">Cliquez sur <strong>Ajouter un objet</strong> pour commencer.</p>
        </div>

        <div id="objetsContainer" class="p-4 pt-0 space-y-3" style="display:none"></div>
    </div>

    <!-- ══════════ ACTIONS ══════════ -->
    <div class="flex items-center justify-between pt-2">
        <a href="/admin/quetes" class="btn-secondary">
            ← Annuler
        </a>
        <button type="submit"
                id="saveBtn"
                class="btn-primary"
                disabled>
            ✅ Créer la quête
        </button>
    </div>

</form>

<!-- Modals -->
<?php require __DIR__ . '/_modal-parcours.php'; ?>

<!-- Modal suppression objet -->
<div id="deleteObjetModal"
     class="fixed inset-0 z-50"
     style="display:none;background:rgba(0,0,0,.55);align-items:center;justify-content:center">
    <div class="modal-box" style="max-width:400px">
        <div class="modal-header">
            <div>
                <h3 class="text-base font-bold text-slate-800">🗑 Supprimer l'objet</h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    "<span id="deleteObjetName" class="font-semibold text-slate-600"></span>"
                </p>
            </div>
            <button type="button" onclick="closeDeleteObjetModal()" class="modal-close-btn">✕</button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-slate-600">
                Cet objet et tous ses parcours associés seront supprimés de la quête. Cette action est irréversible.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeDeleteObjetModal()" class="btn-secondary">Annuler</button>
            <button type="button" id="confirmDeleteObjetBtn"
                    class="btn-primary" style="background:#dc2626;box-shadow:0 2px 8px rgba(220,38,38,.25)">
                🗑 Supprimer
            </button>
        </div>
    </div>
</div>

<!-- Modal confirmation création -->
<div id="confirmSaveModal"
     class="fixed inset-0 z-50"
     style="display:none;background:rgba(0,0,0,.55);align-items:center;justify-content:center">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <div>
                <h3 class="text-base font-bold text-slate-800">✅ Confirmer la création</h3>
                <p class="text-xs text-slate-400 mt-0.5">Récapitulatif avant enregistrement</p>
            </div>
            <button type="button" onclick="closeConfirmSaveModal()" class="modal-close-btn">✕</button>
        </div>
        <div class="modal-body">
            <div id="saveSummary" class="space-y-2"></div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeConfirmSaveModal()" class="btn-secondary">Annuler</button>
            <button type="button" id="confirmSaveBtn" class="btn-primary">
                ✅ Confirmer et créer
            </button>
        </div>
    </div>
</div>

<script src="/js/admin-quetes.js"></script>
