<h1 class="text-2xl font-bold mb-6">✏️ Modifier la quête</h1>

<form method="post"
      action="/admin/quetes/update"
      id="queteForm"
      class="space-y-10">

    <input type="hidden" name="id" value="<?= (int)$quete['id'] ?>">

    <!-- =======================
         QUÊTE
    ======================== -->
    <div class="bg-white rounded-lg shadow p-6 space-y-4 max-w-xl">
        <div>
            <label class="block font-medium mb-1">Nom de la quête</label>
            <input type="text"
                   name="nom"
                   value="<?= htmlspecialchars((string)$quete['nom']) ?>"
                   required
                   class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block font-medium mb-1">Saison</label>
            <input type="text"
                   name="saison"
                   value="<?= htmlspecialchars((string)($quete['saison'] ?? '')) ?>"
                   class="w-full border rounded px-3 py-2">
        </div>
    </div>

    <!-- =======================
         OBJETS
    ======================== -->
    <div class="space-y-4 max-w-5xl">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">🎒 Objets de la quête</h2>

            <button type="button"
                    onclick="addObjet()"
                    class="text-blue-600 text-sm font-medium">
                ➕ Ajouter un objet
            </button>
        </div>

        <div id="objetsContainer" class="space-y-4">
            <?php
            $index = 0;
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

            <?php foreach ($grouped as $objet): ?>

                <div class="bg-white border rounded-xl p-4 space-y-4"
                     data-objet="<?= $index ?>">

                    <input type="hidden"
                           name="objets[<?= $index ?>][id]"
                           value="<?= (int)$objet['id'] ?>">

                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <input type="text"
                                   name="objets[<?= $index ?>][nom]"
                                   value="<?= htmlspecialchars((string)$objet['nom']) ?>"
                                   required
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span class="badge-<?= $index ?> text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">
                                <?= count($objet['parcours'] ?? []) ?> parcours
                            </span>

                            <button type="button"
                                    onclick="confirmDeleteObjet(<?= $index ?>)"
                                    class="text-red-600 hover:text-red-800"
                                    title="Supprimer l’objet">
                                🗑
                            </button>
                        </div>
                    </div>

                    <div class="parcours-list space-y-2 pl-3">
                        <?php foreach ($objet['parcours'] ?? [] as $p): ?>
                            <div class="flex items-center justify-between gap-3 bg-gray-50 border rounded-lg p-2">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($p['logo'])): ?>
                                        <img src="<?= htmlspecialchars($p['logo']) ?>"
                                             class="w-8 h-8 object-contain">
                                    <?php endif; ?>

                                    <div class="leading-tight">
                                        <strong><?= htmlspecialchars($p['titre']) ?></strong><br>
                                        <span class="text-xs text-gray-600">
                                            <?= htmlspecialchars($p['ville']) ?>
                                            (<?= htmlspecialchars($p['departement_code']) ?>)
                                        </span>
                                    </div>
                                </div>

                                <button type="button"
                                        onclick="removeParcours(this)"
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    ✖
                                </button>

                                <input type="hidden"
                                       name="objets[<?= $index ?>][parcours][]"
                                       value="<?= (int)$p['parcours_id'] ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button"
                            onclick="openParcoursModal(<?= $index ?>)"
                            class="text-blue-600 text-sm font-medium">
                        ➕ Ajouter un parcours
                    </button>
                </div>

                <?php $index++; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- =======================
         ACTIONS
    ======================== -->
    <div class="flex justify-between max-w-5xl pt-6">
        <a href="/admin/quetes" class="text-gray-600 underline">
            Annuler
        </a>

        <button type="submit"
                id="saveBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
            Enregistrer les modifications
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
    <div class="bg-white rounded-lg p-6 w-full max-w-lg space-y-4">
        <h3 class="font-semibold text-lg">Confirmer les modifications</h3>

        <p class="text-sm text-gray-600">
            Voici un récapitulatif des changements détectés :
        </p>

        <div id="saveSummary"
             class="space-y-2 text-sm max-h-64 overflow-auto border rounded p-3 bg-gray-50">
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <button type="button"
                    onclick="closeConfirmSaveModal()"
                    class="px-4 py-2 border rounded">
                Annuler
            </button>

            <button type="button"
                    id="confirmSaveBtn"
                    class="px-4 py-2 bg-blue-600 text-white rounded">
                Confirmer et enregistrer
            </button>
        </div>
    </div>
</div>

<script src="/js/admin-quetes.js" defer></script>
