<?php
require_once __DIR__ . '/../../Core/Csrf.php';

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: /login');
    exit;
}
?>

<h1 class="text-2xl font-bold mb-6">Éditer mon profil</h1>

<form
    method="post"
    action="/user/update-profile"
    class="space-y-4 max-w-md"
    onsubmit="event.preventDefault(); teraConfirm({
        form: this,
        title: 'Modifier le profil',
        message: 'Confirmez-vous la modification de vos informations personnelles ?'
    });"
>
    <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

    <div>
        <label class="block text-sm font-medium">Nom d’utilisateur</label>
        <input
            type="text"
            value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            disabled
            class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600"
        >
    </div>

    <div>
        <label class="block text-sm font-medium">Email</label>
        <input
            type="email"
            name="email"
            required
            value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            class="w-full border rounded px-3 py-2"
        >
    </div>

    <div>
        <label class="block text-sm font-medium">Nouveau mot de passe</label>
        <input
            type="password"
            name="new_password"
            placeholder="Laisser vide pour ne pas changer"
            class="w-full border rounded px-3 py-2"
        >
    </div>

    <div>
        <label class="block text-sm font-medium">Mot de passe actuel</label>
        <input
            type="password"
            name="current_password"
            required
            class="w-full border rounded px-3 py-2"
        >
    </div>

    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        Enregistrer
    </button>
</form>
