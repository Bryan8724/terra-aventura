<style>
@media (max-width: 639px) {
    .users-table-wrap { display: none; }
    .users-cards { display: flex; flex-direction: column; gap: .75rem; }
}
@media (min-width: 640px) {
    .users-cards { display: none; }
}
.user-card-mob {
    background: #fff; border-radius: 1rem; border: 1.5px solid #e2e8f0;
    padding: 1rem; display: flex; flex-direction: column; gap: .5rem;
}
.user-card-mob-header { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.user-card-mob-actions { display: flex; gap: .5rem; margin-top: .25rem; }
.user-card-mob-actions a,
.user-card-mob-actions button {
    flex: 1; padding: .5rem; border-radius: .65rem;
    font-size: .8rem; font-weight: 600; text-align: center; border: none; cursor: pointer;
}
</style>

<div class="space-y-5">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">👥 Utilisateurs</h1>
            <p class="text-sm text-slate-400"><?= count($users) ?> compte<?= count($users) > 1 ? 's' : '' ?></p>
        </div>
        <a href="/admin/users/create"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white
                  rounded-xl text-sm font-semibold hover:bg-indigo-700 shadow-sm transition">
            ➕ Ajouter
        </a>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'last_admin'): ?>
        <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
            ⚠️ Impossible de supprimer le dernier administrateur.
        </div>
    <?php endif; ?>

    <!-- VERSION TABLE (desktop) -->
    <div class="users-table-wrap overflow-x-auto bg-white rounded-2xl shadow-sm border border-slate-100">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wide">Utilisateur</th>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-400 uppercase tracking-wide">Email</th>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wide">Rôle</th>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wide">Statut</th>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-400 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr class="border-t border-slate-50 hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                     style="background:<?= ($u['role'] === 'admin') ? '#4f46e5' : '#0891b2' ?>">
                                    <?= strtoupper(substr($u['username'], 0, 2)) ?>
                                </div>
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($u['username']) ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-500 text-sm"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="px-5 py-3.5 text-center">
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-indigo-100 text-indigo-700">🔑 Admin</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-sky-100 text-sky-700">👤 User</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <?php if (($u['status'] ?? 'active') === 'active'): ?>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">✅ Actif</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700">🚫 Désactivé</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex justify-center items-center gap-3">
                                <a href="/admin/users/edit?id=<?= $u['id'] ?>"
                                   class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold transition">✏️ Éditer</a>
                                <form method="post" action="/admin/users/delete"
                                      data-confirm="Supprimer <?= htmlspecialchars($u['username']) ?> ?"
                                      data-confirm-icon="👤"
                                      data-confirm-sub="Cette action est irréversible."
                                      data-confirm-ok="Supprimer">
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                    <button class="text-red-500 hover:text-red-700 text-sm font-semibold transition">🗑 Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Aucun utilisateur trouvé.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- VERSION CARDS (mobile) -->
    <div class="users-cards">
        <?php foreach ($users as $u): ?>
        <div class="user-card-mob">
            <div class="user-card-mob-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                         style="background:<?= ($u['role'] === 'admin') ? '#4f46e5' : '#0891b2' ?>">
                        <?= strtoupper(substr($u['username'], 0, 2)) ?>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($u['username']) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($u['email']) ?></p>
                    </div>
                </div>
                <div class="flex gap-1.5">
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= $u['role'] === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-sky-100 text-sky-700' ?>">
                        <?= $u['role'] === 'admin' ? '🔑' : '👤' ?>
                    </span>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full <?= ($u['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= ($u['status'] ?? 'active') === 'active' ? '✅' : '🚫' ?>
                    </span>
                </div>
            </div>
            <div class="user-card-mob-actions">
                <a href="/admin/users/edit?id=<?= $u['id'] ?>"
                   style="background:#eef2ff;color:#4f46e5">✏️ Éditer</a>
                <form method="post" action="/admin/users/delete" style="flex:1"
                      data-confirm="Supprimer <?= htmlspecialchars($u['username']) ?> ?"
                      data-confirm-icon="👤" data-confirm-sub="Irréversible." data-confirm-ok="Supprimer">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" style="width:100%;background:#fee2e2;color:#dc2626">🗑 Supprimer</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
            <div class="text-center text-slate-400 py-8">Aucun utilisateur trouvé.</div>
        <?php endif; ?>
    </div>

</div>
