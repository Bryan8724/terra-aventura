<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-gray-100">

<h1 class="text-2xl mb-6">
    <?= isset($user) ? 'Modifier utilisateur' : 'Créer utilisateur' ?>
</h1>

<form method="post" action="<?= isset($user) ? '/admin/users/update' : '/admin/users/store' ?>"
      class="bg-white p-6 rounded shadow space-y-4">

<?php if (isset($user)): ?>
<input type="hidden" name="id" value="<?= $user['id'] ?>">
<?php endif; ?>

<input name="username" placeholder="Username" required
       value="<?= $user['username'] ?? '' ?>" class="border w-full p-2">

<input name="email" type="email" placeholder="Email" required
       value="<?= $user['email'] ?? '' ?>" class="border w-full p-2">

<input name="password" type="password" placeholder="Mot de passe (optionnel)"
       class="border w-full p-2">

<select name="role" class="border w-full p-2">
    <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
    <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
</select>

<?php if (isset($user)): ?>
<select name="status" class="border w-full p-2">
    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Actif</option>
    <option value="disabled" <?= $user['status'] === 'disabled' ? 'selected' : '' ?>>Désactivé</option>
</select>
<?php endif; ?>

<button class="bg-gray-900 text-white px-4 py-2 rounded">
    Enregistrer
</button>

</form>

</body>
</html>
