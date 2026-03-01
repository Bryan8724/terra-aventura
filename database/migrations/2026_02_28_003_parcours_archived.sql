-- Migration : ajout colonne archived sur la table parcours
-- Utilise IF NOT EXISTS pour éviter l'erreur si sync-schema l'a déjà créée

SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'parcours'
      AND COLUMN_NAME  = 'archived'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE parcours ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0 AFTER date_fin',
    'SELECT ''colonne archived déjà présente, skip'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index (ignore si déjà existant)
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'parcours'
      AND INDEX_NAME   = 'idx_parcours_archived'
);

SET @sql2 = IF(@idx_exists = 0,
    'CREATE INDEX idx_parcours_archived ON parcours (archived)',
    'SELECT ''index idx_parcours_archived déjà présent, skip'''
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
