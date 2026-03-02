-- =====================================================
--  Migration : Confirmation explicite des objets de quête
--              + Parcours final utilisateur
-- =====================================================

-- Table : confirmation explicite qu'un objet a été obtenu par un user
CREATE TABLE IF NOT EXISTS quete_objet_confirmes (
    id              INT(11) NOT NULL AUTO_INCREMENT,
    user_id         INT(11) NOT NULL,
    quete_objet_id  INT(11) NOT NULL,
    date_confirmation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_objet (user_id, quete_objet_id),
    KEY idx_user_id (user_id),
    KEY idx_objet_id (quete_objet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table : parcours final saisi par l'utilisateur une fois tous les objets obtenus
CREATE TABLE IF NOT EXISTS quete_parcours_final (
    id           INT(11) NOT NULL AUTO_INCREMENT,
    user_id      INT(11) NOT NULL,
    quete_id     INT(11) NOT NULL,
    titre        VARCHAR(255) NOT NULL,
    ville        VARCHAR(255) DEFAULT NULL,
    distance_km  DECIMAL(6,2) DEFAULT NULL,
    date_validation DATETIME DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_quete (user_id, quete_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;