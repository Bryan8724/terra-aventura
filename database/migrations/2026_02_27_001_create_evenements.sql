CREATE TABLE IF NOT EXISTS `evenements` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `nom`              VARCHAR(255) NOT NULL,
  `ville`            VARCHAR(100) NOT NULL,
  `departement_code` VARCHAR(10)  NOT NULL,
  `departement_nom`  VARCHAR(100) NOT NULL DEFAULT '',
  `date_debut`       DATE         NOT NULL,
  `date_fin`         DATE         NOT NULL,
  `image`            VARCHAR(255) DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `evenement_effectues` (
  `id`               INT(11)  NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11)  NOT NULL,
  `evenement_id`     INT(11)  NOT NULL,
  `date_validation`  DATE     DEFAULT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_user` (`user_id`, `evenement_id`),
  KEY `fk_ee_event` (`evenement_id`),
  CONSTRAINT `fk_ee_event` FOREIGN KEY (`evenement_id`) REFERENCES `evenements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `evenement_parcours` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `evenement_id`  INT(11)      NOT NULL,
  `titre`         VARCHAR(255) NOT NULL,
  `niveau`        INT(11)      NOT NULL DEFAULT 3,
  `terrain`       INT(11)      NOT NULL DEFAULT 3,
  `duree`         VARCHAR(50)  DEFAULT NULL,
  `distance_km`   FLOAT        DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ep_evenement` (`evenement_id`),
  CONSTRAINT `fk_ep_evenement` FOREIGN KEY (`evenement_id`) REFERENCES `evenements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `evenement_parcours_effectues` (
  `id`                    INT(11)  NOT NULL AUTO_INCREMENT,
  `user_id`               INT(11)  NOT NULL,
  `evenement_parcours_id` INT(11)  NOT NULL,
  `date_validation`       DATE     DEFAULT NULL,
  `created_at`            DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ep_user` (`user_id`, `evenement_parcours_id`),
  KEY `fk_epe_ep` (`evenement_parcours_id`),
  CONSTRAINT `fk_epe_ep` FOREIGN KEY (`evenement_parcours_id`) REFERENCES `evenement_parcours` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
