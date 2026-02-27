<?php
$user    = $_SESSION['user'] ?? null;
$isAdmin = ($user['role'] ?? '') === 'admin';

function evIsActive(?string $debut, ?string $fin): bool {
    if (!$debut || !$fin) return false;
    $today = new DateTime('today');
    return new DateTime($debut) <= $today && $today <= new DateTime($fin);
}
function evIsExpired(?string $fin): bool {
    if (!$fin) return false;
    return new DateTime($fin) < new DateTime('today');
}
function evFormatDate(?string $d): string {
    if (!$d) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt ? $dt->format('d/m/Y') : $d;
}
?>
<style>
.ev-card{background:#fff;border-radius:1rem;border:1px solid #f1f5f9;overflow:hidden;transition:box-shadow .18s,transform .18s}
.ev-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08);transform:translateY(-1px)}
.ev-card.done{border-left:4px solid #ea580c}
.ev-card.active-now{border-left:4px solid #16a34a}
.ev-card.expired{border-left:4px solid #94a3b8;opacity:.75}
.ev-banner{height:120px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:3rem;position:relative;overflow:hidden}
.ev-banner img{width:100%;height:100%;object-fit:cover}
.ev-status{position:absolute;top:.5rem;right:.5rem;padding:.2rem .65rem;border-radius:9999px;font-size:.7rem;font-weight:700}
.ev-status.active{background:#dcfce7;color:#15803d}
.ev-status.expired{background:#f1f5f9;color:#64748b}
.ev-status.upcoming{background:#fef9c3;color:#854d0e}
.ev-status.done{background:#ffedd5;color:#c2410c}
.btn-ev{display:inline-flex;align-items:center;gap:.3rem;padding:.45rem .9rem;border-radius:.65rem;font-size:.8rem;font-weight:600;border:none;cursor:pointer;transition:all .18s}
.btn-ev.orange{background:#ea580c;color:#fff}.btn-ev.orange:hover{background:#c2410c}
.btn-ev.done{background:#ffedd5;color:#c2410c;border:1.5px solid #fed7aa}.btn-ev.done:hover{background:#fed7aa}
.btn-ev.disabled{background:#f1f5f9;color:#94a3b8;border:1.5px solid #e2e8f0;cursor:not-allowed}
.admin-btn{display:inline-flex;align-items:center;justify-content:center;width:1.9rem;height:1.9rem;border-radius:.5rem;font-size:.8rem;border:none;cursor:pointer;transition:all .15s}
.admin-btn.edit{background:#fef3c7;color:#b45309}.admin-btn.edit:hover{background:#fde68a}
.admin-btn.del{background:#fee2e2;color:#dc2626}.admin-btn.del:hover{background:#fecaca}
</style>

<?php if (empty($evenements)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
        <div class="text-4xl mb-3">🎉</div>
        <p class="text-gray-500 font-medium">Aucun événement trouvé</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($evenements as $ev): ?>
        <?php
        $isNow     = evIsActive($ev['date_debut'], $ev['date_fin']);
        $isExpired = evIsExpired($ev['date_fin']);
        $isDone    = (bool)$ev['effectue'];

        if ($isDone)        $cardClass = 'done';
        elseif ($isNow)     $cardClass = 'active-now';
        elseif ($isExpired) $cardClass = 'expired';
        else                $cardClass = '';

        if ($isDone)        [$stCls,$stLabel] = ['done',   '🎉 Participé'];
        elseif ($isNow)     [$stCls,$stLabel] = ['active', '🟢 En cours'];
        elseif ($isExpired) [$stCls,$stLabel] = ['expired','⛔ Terminé'];
        else                [$stCls,$stLabel] = ['upcoming','🕐 À venir'];
        ?>
        <div class="ev-card <?= $cardClass ?>">

            <!-- Bannière -->
            <div class="ev-banner">
                <?php if (!empty($ev['image'])): ?>
                    <img src="<?= htmlspecialchars($ev['image']) ?>" alt="">
                <?php else: ?>
                    🎉
                <?php endif; ?>
                <span class="ev-status <?= $stCls ?>"><?= $stLabel ?></span>
            </div>

            <!-- Corps -->
            <div class="p-4">
                <h3 class="font-bold text-gray-800 text-base leading-snug mb-1">
                    <?= htmlspecialchars($ev['nom']) ?>
                </h3>
                <div class="flex flex-wrap gap-x-3 text-xs text-gray-400 mb-3">
                    <span>📍 <?= htmlspecialchars($ev['ville']) ?> (<?= $ev['departement_code'] ?>)</span>
                    <span>📅 <?= evFormatDate($ev['date_debut']) ?> → <?= evFormatDate($ev['date_fin']) ?></span>
                    <?php if ((int)$ev['nb_parcours'] > 0): ?>
                        <span>🗺️ <?= $ev['nb_parcours'] ?> parcours
                            <?php if ((int)$ev['nb_parcours_faits'] > 0): ?>
                                <span class="text-orange-500 font-semibold">(<?= $ev['nb_parcours_faits'] ?>/<?= $ev['nb_parcours'] ?>)</span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <!-- Détail parcours -->
                    <?php if ((int)$ev['nb_parcours'] > 0): ?>
                        <a href="/evenement/detail?id=<?= (int)$ev['id'] ?>"
                           class="btn-ev" style="background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0">
                            🗺️ Voir les parcours
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <div class="flex items-center gap-2">
                        <!-- Bouton validation participation -->
                        <?php if ($isDone): ?>
                            <form method="post" action="/evenement/reset" onsubmit="return confirm('Retirer votre participation ?')">
                                <input type="hidden" name="evenement_id" value="<?= $ev['id'] ?>">
                                <button type="submit" class="btn-ev done">🎉 Participé</button>
                            </form>
                        <?php elseif ($isExpired): ?>
                            <button class="btn-ev disabled" disabled>⛔ Terminé</button>
                        <?php else: ?>
                            <button onclick="openEvModal(<?= $ev['id'] ?>)" class="btn-ev orange">
                                🎉 Valider
                            </button>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                            <a href="/evenement/edit?id=<?= $ev['id'] ?>" class="admin-btn edit">✏️</a>
                            <form method="post" action="/evenement/delete"
                                  onsubmit="return confirm('Supprimer cet événement ?')">
                                <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                <button type="submit" class="admin-btn del">🗑</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
