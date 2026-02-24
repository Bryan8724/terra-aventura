<?php
// $p = données du POIZ, $isAdmin = bool
$nbParcours = (int)($p['nb_parcours'] ?? 0);
$isActive   = (bool)($p['actif'] ?? 1);
?>
<div class="poiz-card-wrap" data-name="<?= htmlspecialchars(strtolower($p['nom'])) ?>" data-theme="<?= htmlspecialchars(strtolower($p['theme'] ?? '')) ?>">
    <div class="poiz-card">

        <!-- Image -->
        <div class="poiz-card-img">
            <?php if (!$isActive): ?>
                <span class="inactive-badge">Inactif</span>
            <?php endif; ?>
            <?php if (!empty($p['logo'])): ?>
                <img src="<?= htmlspecialchars($p['logo']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
            <?php else: ?>
                <div class="text-4xl text-gray-300">📍</div>
            <?php endif; ?>
        </div>

        <!-- Corps -->
        <div class="poiz-card-body">
            <p class="poiz-name"><?= htmlspecialchars($p['nom']) ?></p>
            <?php if (!empty($p['theme'])): ?>
                <span class="poiz-theme">🏷 <?= htmlspecialchars($p['theme']) ?></span>
            <?php endif; ?>
            <p class="poiz-count mt-auto pt-1">
                <?= $nbParcours ?> parcours lié<?= $nbParcours > 1 ? 's' : '' ?>
            </p>
        </div>

        <!-- Actions admin -->
        <?php if ($isAdmin): ?>
            <div class="poiz-actions">
                <a href="/poiz/edit?id=<?= (int)$p['id'] ?>" class="btn-edit">
                    ✏️ Modifier
                </a>
                <?php if ($nbParcours === 0): ?>
                    <form method="post" action="/poiz/delete" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($p['nom'])) ?> » ?')">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn-delete w-full">🗑 Supprimer</button>
                    </form>
                <?php else: ?>
                    <span class="btn-locked" title="Utilisé dans <?= $nbParcours ?> parcours">🔒 Utilisé</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
