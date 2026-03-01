<?php require __DIR__ . '/_form-styles.php'; ?>

<!-- ── Breadcrumb ── -->
<div class="flex items-center gap-2 text-sm text-slate-400 mb-6">
    <a href="/admin/quetes" class="hover:text-indigo-600 transition">🎯 Quêtes</a>
    <span>›</span>
    <span class="text-slate-700 font-medium">Modifier — <?= htmlspecialchars((string)$quete['nom']) ?></span>
</div>

<!-- ── Titre ── -->
<div class="flex items-center gap-3 mb-7">
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600
                flex items-center justify-center text-white text-lg shadow-md">
        ✏️
    </div>
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Modifier la quête</h1>
        <p class="text-sm text-slate-400">
            Modifiez les informations, objets et parcours associés
        </p>
    </div>
</div>

<form method="post"
      action="/admin/quetes/update"
      id="queteForm"
      class="space-y-5 max-w-3xl">

    <input type="hidden" name="id" value="<?= (int)$quete['id'] ?>">

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
                       value="<?= htmlspecialchars((string)$quete['nom']) ?>"
                       class="field-input">
            </div>
            <div>
                <label class="field-label" for="saison">Saison</label>
                <input type="text"
                       id="saison"
                       name="saison"
                       value="<?= htmlspecialchars((string)($quete['saison'] ?? '')) ?>"
                       placeholder="Facultatif"
                       class="field-input">
                <p class="field-hint">Laissez vide si la quête n'est pas liée à une saison.</p>
            </div>
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

        <div id="objetsContainer" class="p-4 space-y-3">
        <?php
        /* ── Reconstruction des objets groupés ── */
        $index   = 0;
        $grouped = [];
        foreach ($objets as $row) {
            $oid = (int)$row['objet_id'];
            $grouped[$oid]['id']  = $oid;
            $grouped[$oid]['nom'] = $row['objet_nom'];
            if (!empty($row['parcours_id'])) {
                $grouped[$oid]['parcours'][] = $row;
            }
        }
        ?>
        <?php foreach ($grouped as $objet):
            $nbParcours = count($objet['parcours'] ?? []);
        ?>

        <div class="objet-card" data-objet="<?= $index ?>">

            <div class="objet-card-header">
                <span class="objet-number"><?= $index + 1 ?></span>

                <input type="hidden"
                       name="objets[<?= $index ?>][id]"
                       value="<?= (int)$objet['id'] ?>">

                <input type="text"
                       name="objets[<?= $index ?>][nom]"
                       value="<?= htmlspecialchars((string)$objet['nom']) ?>"
                       required
                       placeholder="Nom de l'objet"
                       class="objet-name-input"
                       oninput="updateSaveBtn()">

                <span class="badge-parcours badge-<?= $index ?> <?= $nbParcours > 0 ? 'badge-has' : '' ?>">
                    <?= $nbParcours ?> parcours
                </span>

                <button type="button"
                        onclick="confirmDeleteObjet(<?= $index ?>)"
                        class="objet-delete-btn"
                        title="Supprimer cet objet">
                    🗑
                </button>
            </div>

            <!-- Liste parcours -->
            <div class="parcours-list">
            <?php foreach ($objet['parcours'] ?? [] as $p): ?>
                <div class="parcours-item-row">
                    <?php if (!empty($p['logo'])): ?>
                        <img src="<?= htmlspecialchars($p['logo']) ?>"
                             alt="" loading="lazy"
                             class="parcours-item-logo">
                    <?php else: ?>
                        <div class="parcours-item-logo-ph">📍</div>
                    <?php endif; ?>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-700 truncate">
                            <?= htmlspecialchars($p['titre']) ?>
                        </p>
                        <p class="text-xs text-slate-400">
                            <?= htmlspecialchars($p['ville']) ?>
                            (<?= htmlspecialchars($p['departement_code']) ?>)
                        </p>
                    </div>

                    <button type="button"
                            onclick="removeParcours(this)"
                            class="parcours-remove-btn"
                            title="Retirer ce parcours">✕</button>

                    <input type="hidden"
                           name="objets[<?= $index ?>][parcours][]"
                           value="<?= (int)$p['parcours_id'] ?>">
                </div>
            <?php endforeach; ?>
            </div>

            <button type="button"
                    onclick="openParcoursModal(<?= $index ?>)"
                    class="add-parcours-btn">
                <span>➕</span> Ajouter un parcours
            </button>
        </div>

        <?php $index++; endforeach; ?>
        </div>
    </div>

    <!-- ══════════ ACTIONS ══════════ -->
    <div class="flex items-center justify-between pt-2">
        <a href="/admin/quetes" class="btn-secondary">
            ← Annuler
        </a>
        <button type="submit"
                id="saveBtn"
                class="btn-primary btn-primary-blue"
                disabled>
            💾 Enregistrer les modifications
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

<!-- Modal confirmation modifications -->
<div id="confirmSaveModal"
     class="fixed inset-0 z-50"
     style="display:none;background:rgba(0,0,0,.55);align-items:center;justify-content:center">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <div>
                <h3 class="text-base font-bold text-slate-800">💾 Confirmer les modifications</h3>
                <p class="text-xs text-slate-400 mt-0.5">Récapitulatif des changements détectés</p>
            </div>
            <button type="button" onclick="closeConfirmSaveModal()" class="modal-close-btn">✕</button>
        </div>
        <div class="modal-body">
            <div id="saveSummary" class="space-y-2"></div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeConfirmSaveModal()" class="btn-secondary">Annuler</button>
            <button type="button" id="confirmSaveBtn" class="btn-primary btn-primary-blue">
                💾 Confirmer et enregistrer
            </button>
        </div>
    </div>
</div>

<script src="/js/admin-quetes.js"></script>
