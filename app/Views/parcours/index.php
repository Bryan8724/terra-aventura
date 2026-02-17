<?php 
$user = $_SESSION['user'] ?? null;
$isAdmin = isset($user['role']) && $user['role'] === 'admin';
$currentPage = max(1, (int)($_GET['page'] ?? 1));
?>

<!-- 🧭 HEADER -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold">Parcours</h1>
        <p class="text-sm text-gray-500">
            Liste des parcours disponibles et effectués
        </p>
    </div>

    <?php if ($isAdmin): ?>
        <a href="/parcours/create"
           class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            ➕ Ajouter un parcours
        </a>
    <?php endif; ?>
</div>

<div class="space-y-6">

    <!-- 🔎 FILTRES -->
    <form id="filterForm"
          class="bg-white rounded-xl shadow-sm border p-6 space-y-6">

        <div>
            <input type="text"
                   name="search"
                   value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                   placeholder="Rechercher par nom, ville ou département..."
                   class="w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">

            <div>
                <label class="text-sm font-medium">Département</label>
                <select name="departement[]" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">Tous</option>
                    <?php foreach ($departements ?? [] as $code => $nom): ?>
                        <option value="<?= $code ?>"
                            <?= in_array($code, $filters['departements'] ?? []) ? 'selected' : '' ?>>
                            <?= $nom ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium">Poiz</label>
                <select name="poiz_id" class="w-full rounded-lg border-gray-300 text-sm">
                    <option value="">Tous</option>
                    <?php foreach ($poiz as $pz): ?>
                        <option value="<?= $pz['id'] ?>"
                            <?= ($filters['poiz_id'] ?? null) == $pz['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pz['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="effectues"
                    <?= !empty($filters['effectues']) ? 'checked' : '' ?>>
                Parcours effectués
            </label>

            <div class="flex gap-3 justify-end">
                <button type="submit"
                        class="px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm">
                    Filtrer
                </button>
                <a href="/parcours"
                   class="px-5 py-2.5 border rounded-lg text-sm">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <!-- 🗺️ CONTENEUR AJAX -->
    <div id="parcoursContainer">
        <?php require __DIR__ . '/_list.php'; ?>
    </div>

</div>

<script>
function openValidateModal(id) {
    document.getElementById('modalParcoursId').value = id;
    document.getElementById('validateModal').classList.remove('hidden');
}
function closeValidateModal() {
    document.getElementById('validateModal').classList.add('hidden');
}

/* 🔥 AJAX SEARCH + PAGINATION */
const form = document.getElementById('filterForm');
const container = document.getElementById('parcoursContainer');
let timeout;

function loadParcours(params) {
    fetch('/parcours?' + params + '&ajax=1')
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
        });
}

form.addEventListener('submit', function(e) {
    e.preventDefault();
    const params = new URLSearchParams(new FormData(form)).toString();
    loadParcours(params);
});

const searchInput = form.querySelector('input[name="search"]');

searchInput.addEventListener('input', function() {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        const params = new URLSearchParams(new FormData(form)).toString();
        loadParcours(params);
    }, 400);
});
</script>
