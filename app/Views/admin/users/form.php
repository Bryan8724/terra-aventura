<?php
$isEdit   = isset($user);
$action   = $isEdit ? '/admin/users/update' : '/admin/users/store';
$icon     = $isEdit ? '✏️' : '➕';
$initials = $isEdit ? strtoupper(substr($user['username'], 0, 2)) : '??';
$roleColor   = ($user['role'] ?? '') === 'admin' ? '#4f46e5' : '#0891b2';
$statusColor = ($user['status'] ?? 'active') === 'active' ? '#16a34a' : '#dc2626';
$btnColor    = $isEdit ? '#4f46e5' : '#16a34a';
$btnShadow   = $isEdit ? 'rgba(79,70,229,.3)' : 'rgba(22,163,74,.3)';
?>
<style>
.uf-label {
    display:block; font-size:.72rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.06em;
    color:#64748b; margin-bottom:.4rem;
}
.uf-input, .uf-select {
    width:100%; padding:.6rem .875rem;
    border:1.5px solid #e2e8f0; border-radius:.75rem;
    font-size:.875rem; background:#fff; color:#1e293b;
    transition:border-color .15s, box-shadow .15s; outline:none;
    box-sizing:border-box;
}
.uf-select {
    appearance:none; padding-right:2rem;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:calc(100% - .75rem) center;
}
.uf-input:focus, .uf-select:focus {
    border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.1);
}
.uf-hint { font-size:.73rem; color:#94a3b8; margin-top:.3rem; }
.uf-card {
    background:#fff; border:1.5px solid #e2e8f0;
    border-radius:1.125rem; box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden;
    height:100%;
}
.uf-card-header {
    padding:.875rem 1.25rem;
    background:linear-gradient(135deg,#f8fafc,#f1f5f9);
    border-bottom:1.5px solid #e2e8f0;
    display:flex; align-items:center; gap:.5rem;
    font-size:.75rem; font-weight:700; color:#475569;
    text-transform:uppercase; letter-spacing:.06em;
}
.uf-card-body { padding:1.25rem; }
.uf-avatar {
    width:3.25rem; height:3.25rem; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; font-weight:800; color:#fff; flex-shrink:0;
    box-shadow:0 4px 12px rgba(0,0,0,.18);
}
.uf-badge {
    display:inline-flex; align-items:center; gap:.25rem;
    padding:.2rem .65rem; border-radius:99px;
    font-size:.7rem; font-weight:700;
}
</style>

<!-- Breadcrumb -->
<div class="flex items-center gap-2 text-sm text-slate-400 mb-5">
    <a href="/admin/users" class="hover:text-indigo-600 transition">👥 Utilisateurs</a>
    <span>›</span>
    <span class="text-slate-700 font-medium"><?= $isEdit ? 'Modifier' : 'Créer' ?></span>
</div>

<!-- Titre page -->
<div class="flex items-center gap-3 mb-6">
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600
                flex items-center justify-center text-white text-lg shadow-md flex-shrink-0">
        <?= $icon ?>
    </div>
    <div>
        <h1 class="text-xl font-bold text-slate-800 leading-tight">
            <?= $isEdit ? 'Modifier l\'utilisateur' : 'Créer un utilisateur' ?>
        </h1>
        <p class="text-sm text-slate-400">
            <?= $isEdit ? 'Modifiez les informations de ce compte' : 'Créez un nouveau compte utilisateur' ?>
        </p>
    </div>
</div>

<form method="post" action="<?= $action ?>">
<?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
<?php endif; ?>

<!-- Grille principale 3 colonnes -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;align-items:start;margin-bottom:1.25rem">

    <!-- COL 1 : Identité (si edit) ou champs nom+email -->
    <?php if ($isEdit): ?>
    <div class="uf-card">
        <div class="uf-card-header">👤 Identité</div>
        <div class="uf-card-body" style="display:flex;align-items:center;gap:1rem">
            <div class="uf-avatar" style="background:<?= $roleColor ?>">
                <?= $initials ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-800 truncate"><?= htmlspecialchars($user['username']) ?></p>
                <p class="text-xs text-slate-400 truncate mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <div style="display:flex;gap:.375rem;flex-wrap:wrap">
                    <span class="uf-badge"
                          style="background:<?= $roleColor ?>1a;color:<?= $roleColor ?>">
                        <?= ($user['role'] ?? '') === 'admin' ? '🔑 Admin' : '👤 User' ?>
                    </span>
                    <span class="uf-badge"
                          style="background:<?= $statusColor ?>1a;color:<?= $statusColor ?>">
                        <?= ($user['status'] ?? 'active') === 'active' ? '✅ Actif' : '🚫 Désactivé' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- COL 2 : Infos compte -->
    <div class="uf-card">
        <div class="uf-card-header">📋 Compte</div>
        <div class="uf-card-body" style="display:flex;flex-direction:column;gap:.875rem">
            <div>
                <label class="uf-label" for="username">Nom d'utilisateur <span class="text-red-400">*</span></label>
                <input type="text" id="username" name="username" required
                       value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                       placeholder="ex : jean_dupont" class="uf-input">
            </div>
            <div>
                <label class="uf-label" for="email">E-mail <span class="text-red-400">*</span></label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                       placeholder="jean@exemple.fr" class="uf-input">
            </div>
        </div>
    </div>

    <!-- COL 3 : Rôle, statut, mot de passe -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">
        <div class="uf-card" style="height:auto">
            <div class="uf-card-header">⚙️ Accès</div>
            <div class="uf-card-body" style="display:flex;flex-direction:column;gap:.875rem">
                <div>
                    <label class="uf-label" for="role">Rôle</label>
                    <select id="role" name="role" class="uf-select">
                        <option value="user"  <?= ($user['role'] ?? 'user') === 'user'  ? 'selected' : '' ?>>👤 Utilisateur</option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>🔑 Administrateur</option>
                    </select>
                </div>
                <div>
                    <label class="uf-label" for="status">Statut</label>
                    <select id="status" name="status" class="uf-select">
                        <option value="active"   <?= ($user['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>✅ Actif</option>
                        <option value="disabled" <?= ($user['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>🚫 Désactivé</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="uf-card" style="height:auto">
            <div class="uf-card-header">🔒 Mot de passe</div>
            <div class="uf-card-body">
                <label class="uf-label" for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password"
                       placeholder="Laisser vide = inchangé" class="uf-input">
                <p class="uf-hint">Laissez vide pour conserver l'actuel.</p>
            </div>
        </div>
    </div>

    <?php else: /* MODE CREATE — 3 colonnes pour nouveau user */ ?>

    <!-- COL 1 : Identifiant + Email -->
    <div class="uf-card">
        <div class="uf-card-header">📋 Identité</div>
        <div class="uf-card-body" style="display:flex;flex-direction:column;gap:.875rem">
            <div>
                <label class="uf-label" for="username">Nom d'utilisateur <span class="text-red-400">*</span></label>
                <input type="text" id="username" name="username" required
                       placeholder="ex : jean_dupont" class="uf-input">
            </div>
            <div>
                <label class="uf-label" for="email">Adresse e-mail <span class="text-red-400">*</span></label>
                <input type="email" id="email" name="email" required
                       placeholder="jean@exemple.fr" class="uf-input">
            </div>
        </div>
    </div>

    <!-- COL 2 : Rôle + Mot de passe -->
    <div class="uf-card">
        <div class="uf-card-header">🔒 Accès</div>
        <div class="uf-card-body" style="display:flex;flex-direction:column;gap:.875rem">
            <div>
                <label class="uf-label" for="password">Mot de passe <span class="text-red-400">*</span></label>
                <input type="password" id="password" name="password" required
                       placeholder="Minimum 8 caractères" class="uf-input">
            </div>
            <div>
                <label class="uf-label" for="role">Rôle</label>
                <select id="role" name="role" class="uf-select">
                    <option value="user">👤 Utilisateur</option>
                    <option value="admin">🔑 Administrateur</option>
                </select>
            </div>
        </div>
    </div>

    <!-- COL 3 : Recap / aide -->
    <div class="uf-card" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-color:#bae6fd">
        <div class="uf-card-header" style="background:transparent;border-color:#bae6fd;color:#0369a1">
            💡 À savoir
        </div>
        <div class="uf-card-body" style="font-size:.82rem;color:#0369a1;display:flex;flex-direction:column;gap:.625rem">
            <p>🔑 Un <strong>Administrateur</strong> a accès à toutes les fonctions de gestion.</p>
            <p>👤 Un <strong>Utilisateur</strong> peut valider des parcours et suivre ses quêtes.</p>
            <p>📧 L'adresse e-mail sert à l'identification et aux notifications.</p>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Barre d'actions -->
<div style="display:flex;align-items:center;justify-content:space-between;
            padding:1rem 1.25rem;background:#fff;border:1.5px solid #e2e8f0;
            border-radius:1.125rem;box-shadow:0 1px 4px rgba(0,0,0,.05)">
    <a href="/admin/users"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.2rem;
              border-radius:.875rem;border:1.5px solid #e2e8f0;background:#f8fafc;
              color:#475569;font-size:.875rem;font-weight:600;text-decoration:none;
              transition:all .15s"
       onmouseover="this.style.background='#f1f5f9'"
       onmouseout="this.style.background='#f8fafc'">
        ← Annuler
    </a>
    <button type="submit"
            style="display:inline-flex;align-items:center;gap:.5rem;
                   padding:.65rem 1.75rem;border-radius:.875rem;border:none;
                   background:<?= $btnColor ?>;color:#fff;
                   font-size:.9rem;font-weight:700;cursor:pointer;
                   box-shadow:0 2px 10px <?= $btnShadow ?>;
                   transition:all .15s">
        <?= $isEdit ? '💾 Enregistrer les modifications' : '✅ Créer l\'utilisateur' ?>
    </button>
</div>

</form>
