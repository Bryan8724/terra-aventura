#!/usr/bin/env php
<?php

/**
 * ═══════════════════════════════════════════════
 *  Terra Aventura — Script de migration BDD
 *  Usage : php migrate.php
 *  Lancé par le script de déploiement prod
 * ═══════════════════════════════════════════════
 */

define('MIGRATIONS_DIR', __DIR__ . '/database/migrations');

/* ── Connexion PDO ── */
$host     = getenv('DB_HOST')     ?: 'db';
$dbname   = getenv('DB_NAME')     ?: 'terra_aventura';
$user     = getenv('DB_USER')     ?: 'terra';
$password = getenv('DB_PASSWORD') ?: 'terra';

$attempts = 0;
$pdo      = null;

while ($attempts < 5) {
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        break;
    } catch (PDOException $e) {
        $attempts++;
        echo "[migrate] Connexion BDD échouée (tentative $attempts/5) : " . $e->getMessage() . "\n";
        if ($attempts >= 5) {
            echo "[migrate] ERREUR FATALE : impossible de se connecter à la base de données.\n";
            exit(1);
        }
        sleep(3);
    }
}

/* ── Création de la table migrations si absente ── */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        migration    VARCHAR(255) NOT NULL UNIQUE,
        executed_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* ── Migrations déjà jouées ── */
$stmt = $pdo->query("SELECT migration FROM migrations");
$done = $stmt->fetchAll(PDO::FETCH_COLUMN);
$done = array_flip($done); // lookup O(1)

/* ── Lecture des fichiers ── */
if (!is_dir(MIGRATIONS_DIR)) {
    echo "[migrate] Dossier database/migrations/ introuvable — rien à jouer.\n";
    exit(0);
}

$files = glob(MIGRATIONS_DIR . '/*.sql');
sort($files); // ordre alphabétique / chronologique

if (empty($files)) {
    echo "[migrate] Aucun fichier de migration trouvé — base de données à jour.\n";
    exit(0);
}

$ran  = 0;
$skip = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (isset($done[$name])) {
        echo "[migrate] ⏭  $name (déjà exécuté)\n";
        $skip++;
        continue;
    }

    $sql = file_get_contents($file);

    if (empty(trim($sql))) {
        echo "[migrate] ⚠️  $name (fichier vide — ignoré)\n";
        continue;
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);

        $insert = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $insert->execute([$name]);

        $pdo->commit();
        echo "[migrate] ✅  $name exécuté avec succès\n";
        $ran++;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "[migrate] ❌  $name ÉCHEC : " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\n[migrate] Terminé — $ran migration(s) jouée(s), $skip ignorée(s).\n";
exit(0);
