#!/usr/bin/env php
<?php

/**
 * ═══════════════════════════════════════════════════════════
 *  Terra Aventura — Synchronisation de schéma DEV → PROD
 *
 *  Ce script compare le schéma de la BDD dev avec celui de
 *  la BDD prod et applique uniquement les différences
 *  structurelles (nouvelles tables, nouvelles colonnes,
 *  nouveaux index). Les données prod ne sont jamais touchées.
 *
 *  Usage : php sync-schema.php
 * ═══════════════════════════════════════════════════════════
 */

// ── Connexion DEV ──────────────────────────────────────────
$devHost     = getenv('DEV_DB_HOST')     ?: 'terra-aventura-dev-db';
$devDb       = getenv('DEV_DB_NAME')     ?: 'terra_aventura';
$devUser     = getenv('DEV_DB_USER')     ?: 'terra';
$devPassword = getenv('DEV_DB_PASSWORD') ?: 'terra';

// ── Connexion PROD ─────────────────────────────────────────
$prodHost     = getenv('DB_HOST')     ?: 'db';
$prodDb       = getenv('DB_NAME')     ?: 'terra_aventura';
$prodUser     = getenv('DB_USER')     ?: 'terra';
$prodPassword = getenv('DB_PASSWORD') ?: 'terra';

echo "[sync-schema] Connexion aux bases de données...\n";

function connectDb(string $host, string $db, string $user, string $pass, string $label): PDO
{
    $attempts = 0;
    while ($attempts < 5) {
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$db;charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            echo "[sync-schema] ✅ Connecté à $label ($host/$db)\n";
            return $pdo;
        } catch (PDOException $e) {
            $attempts++;
            echo "[sync-schema] ⏳ $label tentative $attempts/5 : " . $e->getMessage() . "\n";
            if ($attempts >= 5) {
                echo "[sync-schema] ❌ ERREUR FATALE : impossible de se connecter à $label\n";
                exit(1);
            }
            sleep(3);
        }
    }
}

$dev  = connectDb($devHost,  $devDb,  $devUser,  $devPassword,  'DEV');
$prod = connectDb($prodHost, $prodDb, $prodUser, $prodPassword, 'PROD');

// ── Récupération des tables ────────────────────────────────
function getTables(PDO $pdo, string $dbName): array
{
    $stmt = $pdo->prepare("
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ?
        AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ");
    $stmt->execute([$dbName]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// ── Récupération des colonnes ──────────────────────────────
function getColumns(PDO $pdo, string $dbName, string $table): array
{
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
               COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT,
               ORDINAL_POSITION
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION
    ");
    $stmt->execute([$dbName, $table]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Récupération des index ─────────────────────────────────
function getIndexes(PDO $pdo, string $dbName, string $table): array
{
    $stmt = $pdo->prepare("
        SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLUMNS,
               NON_UNIQUE
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        GROUP BY INDEX_NAME, NON_UNIQUE
    ");
    $stmt->execute([$dbName, $table]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Récupération du CREATE TABLE complet ───────────────────
function getCreateTable(PDO $pdo, string $table): string
{
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $row  = $stmt->fetch(PDO::FETCH_NUM);
    return $row[1];
}

// ══════════════════════════════════════════════════════════
//  ÉTAPE 1 : Tables manquantes en prod → CREATE TABLE
// ══════════════════════════════════════════════════════════
echo "\n[sync-schema] ── Étape 1 : Vérification des tables ──\n";

$devTables  = getTables($dev,  $devDb);
$prodTables = getTables($prod, $prodDb);
$prodTablesIndex = array_flip($prodTables);

$created = 0;
foreach ($devTables as $table) {
    if (!isset($prodTablesIndex[$table])) {
        echo "[sync-schema] ➕ Table manquante : $table → création...\n";
        $createSql = getCreateTable($dev, $table);
        // Sécurité : ajoute IF NOT EXISTS
        $createSql = preg_replace('/^CREATE TABLE `/i', 'CREATE TABLE IF NOT EXISTS `', $createSql);
        try {
            $prod->exec($createSql);
            echo "[sync-schema] ✅  $table créée\n";
            $created++;
        } catch (PDOException $e) {
            echo "[sync-schema] ❌  Échec création $table : " . $e->getMessage() . "\n";
            exit(1);
        }
    } else {
        echo "[sync-schema] ✔  $table existe en prod\n";
    }
}

if ($created === 0) {
    echo "[sync-schema] Aucune table manquante.\n";
}

// ══════════════════════════════════════════════════════════
//  ÉTAPE 2 : Colonnes manquantes → ALTER TABLE ADD COLUMN
// ══════════════════════════════════════════════════════════
echo "\n[sync-schema] ── Étape 2 : Vérification des colonnes ──\n";

// Recharge les tables prod après création éventuelle
$prodTables      = getTables($prod, $prodDb);
$prodTablesIndex = array_flip($prodTables);

$altered = 0;
foreach ($devTables as $table) {
    if (!isset($prodTablesIndex[$table])) continue; // table pas encore créée (ne devrait pas arriver)

    $devCols  = getColumns($dev,  $devDb,  $table);
    $prodCols = getColumns($prod, $prodDb, $table);

    $prodColNames = array_flip(array_column($prodCols, 'COLUMN_NAME'));
    $lastDevCol   = null;

    foreach ($devCols as $col) {
        $colName = $col['COLUMN_NAME'];

        if (!isset($prodColNames[$colName])) {
            // Construit la définition ALTER TABLE ADD COLUMN
            $type     = $col['COLUMN_TYPE'];
            $nullable = $col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default  = '';

            if ($col['COLUMN_DEFAULT'] !== null) {
                $defVal  = $col['COLUMN_DEFAULT'];
                $default = " DEFAULT " . (
                    in_array(strtolower($defVal), ['current_timestamp()', 'null', 'true', 'false'])
                    ? $defVal
                    : $prod->quote($defVal)
                );
            } elseif ($col['IS_NULLABLE'] === 'YES') {
                $default = " DEFAULT NULL";
            }

            $extra   = $col['EXTRA'] ? ' ' . $col['EXTRA'] : '';
            $comment = $col['COLUMN_COMMENT'] ? " COMMENT " . $prod->quote($col['COLUMN_COMMENT']) : '';
            $after   = $lastDevCol ? " AFTER `$lastDevCol`" : ' FIRST';

            $alterSql = "ALTER TABLE `$table` ADD COLUMN `$colName` $type $nullable$default$extra$comment$after";

            echo "[sync-schema] ➕ Colonne manquante : $table.$colName → ajout...\n";
            try {
                $prod->exec($alterSql);
                echo "[sync-schema] ✅  $table.$colName ajoutée\n";
                $altered++;
            } catch (PDOException $e) {
                echo "[sync-schema] ❌  Échec ajout $table.$colName : " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        $lastDevCol = $colName;
    }
}

if ($altered === 0) {
    echo "[sync-schema] Aucune colonne manquante.\n";
}

// ══════════════════════════════════════════════════════════
//  ÉTAPE 3 : Index manquants → ALTER TABLE ADD INDEX
// ══════════════════════════════════════════════════════════
echo "\n[sync-schema] ── Étape 3 : Vérification des index ──\n";

$indexAdded = 0;
foreach ($devTables as $table) {
    if (!isset($prodTablesIndex[$table])) continue;

    $devIndexes  = getIndexes($dev,  $devDb,  $table);
    $prodIndexes = getIndexes($prod, $prodDb, $table);
    $prodIdxNames = array_flip(array_column($prodIndexes, 'INDEX_NAME'));

    foreach ($devIndexes as $idx) {
        $idxName = $idx['INDEX_NAME'];
        if ($idxName === 'PRIMARY') continue; // Géré à la création de table
        if (isset($prodIdxNames[$idxName])) continue;

        $cols    = implode('`, `', explode(',', $idx['COLUMNS']));
        $unique  = $idx['NON_UNIQUE'] == 0 ? 'UNIQUE ' : '';
        $addIdx  = "ALTER TABLE `$table` ADD {$unique}INDEX `$idxName` (`$cols`)";

        echo "[sync-schema] ➕ Index manquant : $table.$idxName → ajout...\n";
        try {
            $prod->exec($addIdx);
            echo "[sync-schema] ✅  Index $idxName ajouté sur $table\n";
            $indexAdded++;
        } catch (PDOException $e) {
            // Un index qui existe déjà ne doit pas bloquer
            if (str_contains($e->getMessage(), 'Duplicate key name')) {
                echo "[sync-schema] ⚠️  Index $idxName déjà présent (ignoré)\n";
            } else {
                echo "[sync-schema] ❌  Échec ajout index $idxName : " . $e->getMessage() . "\n";
                exit(1);
            }
        }
    }
}

if ($indexAdded === 0) {
    echo "[sync-schema] Aucun index manquant.\n";
}

// ══════════════════════════════════════════════════════════
//  RÉSUMÉ
// ══════════════════════════════════════════════════════════
echo "\n[sync-schema] ══════════════════════════════════════\n";
echo "[sync-schema] Synchronisation terminée :\n";
echo "[sync-schema]   Tables créées  : $created\n";
echo "[sync-schema]   Colonnes ajout : $altered\n";
echo "[sync-schema]   Index ajoutés  : $indexAdded\n";
echo "[sync-schema] Les données prod sont intactes.\n";
echo "[sync-schema] ══════════════════════════════════════\n";

exit(0);