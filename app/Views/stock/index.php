<?php
$user    = $_SESSION['user'] ?? null;
$isAdmin = ($user['role'] ?? '') === 'admin';
?>

<style>
/* Variables du thème — héritées de responsive.css */
.stock-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1rem;
}
@media (max-width: 480px) {
    .stock-grid { grid-template-columns: repeat(2, 1fr); gap: .75rem; }
}
@media (min-width: 1024px) {
    .stock-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
}
</style>

<!-- En-tête -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">🏅 Mon Stock de Badges</h1>
        <p class="text-sm text-gray-500">
            Badges POIZ en votre possession —
            <strong id="total-count"><?= $totalBadges ?></strong> badge<?= $totalBadges > 1 ? 's' : '' ?> au total
        </p>
    </div>
    <button onclick="resetAll()" title="Tout remettre à zéro"
            class="text-xs px-3 py-1.5 rounded-lg border text-gray-500 hover:text-red-600 hover:border-red-300 transition">
        🗑 Tout remettre à 0
    </button>
</div>

<?php if (empty($items)): ?>
<div class="text-center py-16 text-gray-400">
    <div class="text-5xl mb-3">📦</div>
    <p class="font-medium">Aucun POIZ disponible</p>
    <p class="text-sm mt-1">Les POIZ actifs apparaîtront ici</p>
</div>
<?php else: ?>

<!-- Grille des badges -->
<div class="stock-grid" id="stock-grid">
<?php foreach ($items as $item): ?>
    <div class="stock-card" data-poiz="<?= $item['id'] ?>">

        <!-- Logo -->
        <?php if ($item['logo']): ?>
            <img src="<?= htmlspecialchars($item['logo']) ?>"
                 alt="<?= htmlspecialchars($item['nom']) ?>"
                 class="stock-logo" onerror="this.style.display='none'">
        <?php else: ?>
            <div class="stock-logo flex items-center justify-center text-3xl">🏅</div>
        <?php endif; ?>

        <!-- Nom -->
        <div class="stock-nom"><?= htmlspecialchars($item['nom']) ?></div>

        <!-- Quantité affichée -->
        <div class="stock-qty <?= (int)$item['quantite'] === 0 ? 'zero' : '' ?>" id="qty-<?= $item['id'] ?>">
            <?= (int)$item['quantite'] ?>
        </div>

        <!-- Contrôles +/- -->
        <div class="stock-controls">
            <button class="stock-btn minus"
                    onclick="ajuster(<?= $item['id'] ?>, 'minus')"
                    title="Retirer un badge">−</button>

            <input class="stock-input"
                   type="number" min="0" max="9999"
                   value="<?= (int)$item['quantite'] ?>"
                   id="input-<?= $item['id'] ?>"
                   onchange="setValeur(<?= $item['id'] ?>, this.value)"
                   onblur="setValeur(<?= $item['id'] ?>, this.value)">

            <button class="stock-btn plus"
                    onclick="ajuster(<?= $item['id'] ?>, 'plus')"
                    title="Ajouter un badge">+</button>
        </div>

        <!-- Indicateur "sauvegardé" -->
        <div class="stock-saved" id="saved-<?= $item['id'] ?>">✓ sauvegardé</div>
    </div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<script>
const CSRF = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;

async function call(poizId, action, valeur = 0) {
    const body = new URLSearchParams({
        csrf_token: CSRF,
        poiz_id:    poizId,
        action:     action,
        valeur:     valeur,
    });
    const r = await fetch('/stock/update', { method: 'POST', body });
    return await r.json();
}

function showSaved(poizId) {
    const el = document.getElementById('saved-' + poizId);
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 1500);
}

function updateDisplay(poizId, qty) {
    const qtyEl   = document.getElementById('qty-'   + poizId);
    const inputEl = document.getElementById('input-' + poizId);
    if (qtyEl)   { qtyEl.textContent = qty; qtyEl.className = 'stock-qty' + (qty === 0 ? ' zero' : ''); }
    if (inputEl) { inputEl.value = qty; }

    // Met à jour le total affiché
    let total = 0;
    document.querySelectorAll('.stock-qty').forEach(el => {
        total += parseInt(el.textContent) || 0;
    });
    const tc = document.getElementById('total-count');
    if (tc) tc.textContent = total;
}

async function ajuster(poizId, action) {
    const data = await call(poizId, action);
    if (data.success) {
        updateDisplay(poizId, data.quantite);
        showSaved(poizId);
    }
}

// Debounce pour l'input direct
const debounceMap = {};
function setValeur(poizId, valeur) {
    clearTimeout(debounceMap[poizId]);
    debounceMap[poizId] = setTimeout(async () => {
        const v    = Math.max(0, parseInt(valeur) || 0);
        const data = await call(poizId, 'set', v);
        if (data.success) {
            updateDisplay(poizId, data.quantite);
            showSaved(poizId);
        }
    }, 600);
}

async function resetAll() {
    if (!confirm('Remettre tout le stock à zéro ?')) return;
    const poizIds = [...document.querySelectorAll('[data-poiz]')].map(el => el.dataset.poiz);
    for (const id of poizIds) {
        await call(id, 'set', 0);
        updateDisplay(id, 0);
    }
    // Petit feedback
    const tc = document.getElementById('total-count');
    if (tc) tc.textContent = '0';
}
</script>
