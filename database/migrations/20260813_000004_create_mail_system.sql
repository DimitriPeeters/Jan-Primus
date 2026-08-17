-- AEFS v2 mail queue and delivery history.
-- The obsolete mail_logs table contains no required history and is replaced.

DROP TABLE IF EXISTS `mail_logs`;

CREATE TABLE IF NOT EXISTS `mailings` (
    `mailing_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` VARCHAR(50) NOT NULL,
    `doelgroep_type` VARCHAR(30) NOT NULL,
    `doelgroep_json` JSON NULL,
    `event_id` INT NULL,
    `aangemaakt_door` INT NULL,
    `onderwerp` VARCHAR(255) NOT NULL,
    `inhoud_html` LONGTEXT NOT NULL,
    `inhoud_tekst` LONGTEXT NOT NULL,
    `status` ENUM(
        'in_wachtrij',
        'bezig',
        'verzonden',
        'gedeeltelijk_mislukt',
        'mislukt'
    ) NOT NULL DEFAULT 'in_wachtrij',
    `aangemaakt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `bijgewerkt_op` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `voltooid_op` DATETIME NULL,
    PRIMARY KEY (`mailing_id`),
    KEY `idx_mailings_status_created` (`status`, `aangemaakt_op`),
    KEY `idx_mailings_event` (`event_id`),
    KEY `idx_mailings_creator` (`aangemaakt_door`),
    CONSTRAINT `fk_mailings_event`
        FOREIGN KEY (`event_id`)
        REFERENCES `evenementen` (`event_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_mailings_creator`
        FOREIGN KEY (`aangemaakt_door`)
        REFERENCES `gebruikers` (`gebruiker_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mailing_ontvangers` (
    `ontvanger_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mailing_id` BIGINT UNSIGNED NOT NULL,
    `lid_id` INT NULL,
    `email` VARCHAR(255) NOT NULL,
    `naam` VARCHAR(255) NOT NULL,
    `onderwerp` VARCHAR(255) NOT NULL,
    `inhoud_html` LONGTEXT NOT NULL,
    `inhoud_tekst` LONGTEXT NOT NULL,
    `status` ENUM(
        'in_wachtrij',
        'bezig',
        'verzonden',
        'mislukt'
    ) NOT NULL DEFAULT 'in_wachtrij',
    `pogingen` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `volgende_poging_op` DATETIME NULL,
    `vergrendeld_op` DATETIME NULL,
    `verzonden_op` DATETIME NULL,
    `message_id` VARCHAR(255) NULL,
    `foutmelding` TEXT NULL,
    `aangemaakt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `bijgewerkt_op` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ontvanger_id`),
    UNIQUE KEY `uq_mailing_ontvanger_email` (`mailing_id`, `email`),
    KEY `idx_mailing_ontvangers_queue` (
        `status`,
        `volgende_poging_op`,
        `vergrendeld_op`
    ),
    KEY `idx_mailing_ontvangers_mailing_status` (`mailing_id`, `status`),
    KEY `idx_mailing_ontvangers_lid` (`lid_id`),
    CONSTRAINT `fk_mailing_ontvangers_mailing`
        FOREIGN KEY (`mailing_id`)
        REFERENCES `mailings` (`mailing_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_mailing_ontvangers_lid`
        FOREIGN KEY (`lid_id`)
        REFERENCES `leden` (`lid_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mailing_bijlagen` (
    `bijlage_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mailing_id` BIGINT UNSIGNED NOT NULL,
    `originele_naam` VARCHAR(255) NOT NULL,
    `opslagpad` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(150) NOT NULL,
    `bestandsgrootte` BIGINT UNSIGNED NOT NULL,
    `sha256` CHAR(64) NOT NULL,
    `aangemaakt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`bijlage_id`),
    KEY `idx_mailing_bijlagen_mailing` (`mailing_id`),
    CONSTRAINT `fk_mailing_bijlagen_mailing`
        FOREIGN KEY (`mailing_id`)
        REFERENCES `mailings` (`mailing_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `chk_mailing_bijlagen_grootte`
        CHECK (`bestandsgrootte` > 0)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;
