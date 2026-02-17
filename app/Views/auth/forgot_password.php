<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié – Terra Aventura</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">

<div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">

    <div class="text-center mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">
            Mot de passe oublié
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Entrez votre adresse email
        </p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="mb-6 p-3 rounded-lg bg-green-100 text-green-700 text-sm text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="/forgot-password" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Adresse email
            </label>
            <input
                type="email"
                name="email"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-800"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-gray-900 text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition"
        >
            Envoyer la demande
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="/login"
           class="text-sm text-gray-600 hover:text-gray-900 underline">
            Retour à la connexion
        </a>
    </div>
</div>

</body>
</html>
