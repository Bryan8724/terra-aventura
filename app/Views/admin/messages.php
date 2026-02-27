<div class="space-y-6">

    <h1 class="text-2xl font-semibold">
        Demandes de mot de passe
    </h1>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-3 text-center font-medium">Utilisateur</th>
                    <th class="px-4 py-3 text-center font-medium">Email</th>
                    <th class="px-4 py-3 text-center font-medium">Statut</th>
                    <th class="px-4 py-3 text-center font-medium">Date</th>
                    <th class="px-4 py-3 text-center font-medium">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 text-center align-middle">
                            <?= htmlspecialchars($r['username']) ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <?= htmlspecialchars($r['email']) ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <?php if ($r['status'] === 'pending'): ?>
                                <span class="px-2 py-1 text-xs rounded bg-orange-100 text-orange-700">
                                    En attente
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                    Effectué
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <?php if ($r['status'] === 'pending'): ?>
                                <form method="post"
                                      action="/admin/messages/process"
                                      class="flex justify-center gap-2">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <input type="password"
                                           name="password"
                                           placeholder="Nouveau mot de passe"
                                           class="border px-2 py-1 rounded text-sm"
                                           required>
                                    <button class="bg-gray-900 text-white px-3 py-1 rounded">
                                        Valider
                                    </button>
                                </form>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            Aucune demande en attente.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
