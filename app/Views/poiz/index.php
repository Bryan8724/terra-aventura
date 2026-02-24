<?php
$user    = $_SESSION['user'] ?? null;
$isAdmin = isset($user['role']) && $user['role'] === 'admin';

// Grouper par thème
$byTheme = [];
foreach ($poiz as $p) {
    $theme = $p['theme'] ?: 'Sans thème';
    $byTheme[$theme][] = $p;
}
ksort($byTheme);
?>

<style>
.poiz-card {
    background: #fff;
    border-radius: 1.25rem;
    border: 1px solid #f1f5f9;
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
    display: flex; flex-direction: column;
}
.poiz-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.09); transform: translateY(-2px); }

.poiz-card-img {
    width: 100%; aspect-ratio: 1/1;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 1.5rem;
    position: relative; overflow: hidden;
}
.poiz-card-img img {
    width: 100%; height: 100%;
    object-fit: contain;
    transition: transform .3s ease;
}
.poiz-card:hover .poiz-card-img img { transform: scale(1.06); }

.poiz-card-body { padding: 1rem 1.1rem .9rem; flex: 1; display: flex; flex-direction: column; gap: .35rem; }

.poiz-name { font-size: .95rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.poiz-theme {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .7rem; font-weight: 600; color: #6366f1;
    background: #eef2ff; border-radius: 9999px;
    padding: .2rem .6rem; align-self: flex-start;
}
.poiz-count { font-size: .72rem; color: #94a3b8; }

.poiz-actions { display: flex; gap: .5rem; padding: .75rem 1.1rem; border-top: 1px solid #f8fafc; }
.btn-edit {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: .35rem;
    padding: .45rem; border-radius: .65rem; font-size: .75rem; font-weight: 600;
    background: #fef3c7; color: #b45309; border: none; cursor: pointer;
    transition: background .15s; text-decoration: none;
}
.btn-edit:hover { background: #fde68a; }
.btn-delete {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: .35rem;
    padding: .45rem; border-radius: .65rem; font-size: .75rem; font-weight: 600;
    background: #fee2e2; color: #dc2626; border: none; cursor: pointer;
    transition: background .15s;
}
.btn-delete:hover { background: #fecaca; }
.btn-locked {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: .35rem;
    padding: .45rem; border-radius: .65rem; font-size: .75rem; font-weight: 500;
    background: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; cursor: not-allowed;
}

.theme-section-title {
    font-size: .75rem; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .07em;
    display: flex; align-items: center; gap: .5rem;
    margin-bottom: .9rem; padding-bottom: .5rem;
    border-bottom: 1px solid #f1f5f9;
}
.theme-count-badge {
    padding: .1rem .5rem; border-radius: 9999px;
    background: #f1f5f9; color: #64748b;
    font-size: .68rem; font-weight: 600;
}

/* Recherche */
.search-wrapper { position: relative; }
.search-wrapper svg { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
#poizSearch { padding-left: 2.5rem; transition: border-color .2s, box-shadow .2s; }
#poizSearch:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); outline: none; }

/* Inactive badge */
.inactive-badge {
    position: absolute; top: .6rem; right: .6rem;
    font-size: .65rem; font-weight: 700; padding: .15rem .5rem;
    background: rgba(0,0,0,.55); color: #fff; border-radius: 9999px;
}
</style>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">POIZ</h1>
        <p class="text-sm text-gray-400 mt-0.5"><?= count($poiz) ?> personnage<?= count($poiz) > 1 ? 's' : '' ?> disponible<?= count($poiz) > 1 ? 's' : '' ?></p>
    </div>
    <div class="flex items-center gap-3">
        <!-- Recherche -->
        <div class="search-wrapper">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" id="poizSearch" placeholder="Rechercher un POIZ…"
                   class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm w-52 placeholder-gray-400 text-gray-800">
        </div>
        <?php if ($isAdmin): ?>
            <a href="/poiz/create"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 shadow-sm transition whitespace-nowrap">
                ➕ Ajouter
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($poiz)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <div class="text-5xl mb-3">📍</div>
        <p class="text-gray-500 font-medium">Aucun POIZ disponible</p>
    </div>
<?php else: ?>

    <?php if (count($byTheme) > 1): ?>
        <!-- Vue par thème -->
        <?php foreach ($byTheme as $theme => $items): ?>
            <div class="mb-8 poiz-theme-group" data-theme="<?= htmlspecialchars(strtolower($theme)) ?>">
                <div class="theme-section-title">
                    <span><?= htmlspecialchars($theme) ?></span>
                    <span class="theme-count-badge"><?= count($items) ?></span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
                    <?php foreach ($items as $p): ?>
                        <?php include __DIR__ . '/_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4" id="poizGrid">
            <?php foreach ($poiz as $p): ?>
                <?php include __DIR__ . '/_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<script>
const searchInput = document.getElementById('poizSearch');
searchInput?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.poiz-card-wrap').forEach(wrap => {
        const name  = wrap.dataset.name || '';
        const theme = wrap.dataset.theme || '';
        wrap.style.display = (!q || name.includes(q) || theme.includes(q)) ? '' : 'none';
    });
    // Cacher les sections vides
    document.querySelectorAll('.poiz-theme-group').forEach(group => {
        const visible = [...group.querySelectorAll('.poiz-card-wrap')].some(c => c.style.display !== 'none');
        group.style.display = visible ? '' : 'none';
    });
});
</script>
