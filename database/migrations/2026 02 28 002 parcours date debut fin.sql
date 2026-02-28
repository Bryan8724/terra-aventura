-- ============================================================
--  Migration : ajout colonnes date_debut / date_fin sur parcours
--  Ces colonnes sont utilisées par les parcours Zaméla (éphémères)
-- ============================================================

ALTER TABLE `parcours`
    ADD COLUMN IF NOT EXISTS `date_debut` DATE DEFAULT NULL AFTER `distance_km`,
    ADD COLUMN IF NOT EXISTS `date_fin`   DATE DEFAULT NULL AFTER `date_debut`;