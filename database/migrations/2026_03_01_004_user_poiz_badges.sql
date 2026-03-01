-- Migration : création table user_poiz_badges
-- Stock de badges POIZ par utilisateur

CREATE TABLE IF NOT EXISTS user_poiz_badges (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED    NOT NULL,
    poiz_id    INT UNSIGNED    NOT NULL,
    quantite   INT UNSIGNED    NOT NULL DEFAULT 0,
    updated_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_poiz (user_id, poiz_id),
    KEY idx_user_id (user_id),
    KEY idx_poiz_id (poiz_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
