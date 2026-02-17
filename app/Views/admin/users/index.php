<div class="space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold">Utilisateurs</h1>

        <a href="/admin/users/create"
           class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800">
            + Ajouter
        </a>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'last_admin'): ?>
        <div class="p-3 rounded bg-red-100 text-red-700 text-sm">
            Impossible de supprimer le dernier administrateur.
        </div>
    <?php endif; ?>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-3 text-center font-medium">Utilisateur</th>
                    <th class="px-4 py-3 text-center font-medium">Email</th>
                    <th class="px-4 py-3 text-center font-medium">Rôle</th>
                    <th class="px-4 py-3 text-center font-medium">Statut</th>
                    <th class="px-4 py-3 text-center font-medium">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 text-center align-middle">
                            <?= htmlspecialchars($u['username']) ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <?= htmlspecialchars($u['email']) ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">
                                    Admin
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">
                                    Utilisateur
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                    Actif
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">
                                    Désactivé
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3 text-center align-middle">
                            <div class="flex justify-center items-center gap-4">
                                <a href="/admin/users/edit?id=<?= $u['id'] ?>"
                                   class="text-blue-600 hover:underline">
                                    Éditer
                                </a>

                                <form method="post"
                                      action="/admin/users/delete"
                                      onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button class="text-red-600 hover:underline">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            Aucun utilisateur trouvé.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
