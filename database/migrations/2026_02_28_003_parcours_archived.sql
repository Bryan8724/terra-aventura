-- Migration : ajout colonne archived sur la table parcours
-- Permet de déplacer les parcours des saisons passées dans les "Parcours archivés"

ALTER TABLE parcours
    ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0 AFTER date_fin;

CREATE INDEX idx_parcours_archived ON parcours (archived);
