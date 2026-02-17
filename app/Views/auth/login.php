<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion – Terra Aventura</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">

<div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
    
    <div class="text-center mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">
            Connexion
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Accédez à votre espace Terra Aventura
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-6 p-3 rounded-lg bg-red-100 text-red-700 text-sm text-center">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/login" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Utilisateur ou email
            </label>
            <input
                type="text"
                name="login"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Mot de passe
            </label>
            <input
                type="password"
                name="password"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-gray-900 text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition"
        >
            Se connecter
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="/forgot-password"
           class="text-sm text-gray-600 hover:text-gray-900 underline">
            Mot de passe oublié ?
        </a>
    </div>
</div>

</body>
</html>
