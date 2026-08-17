-- AEFS v2
-- Centrale, niet-geheime applicatie-instellingen en een historisch vaste
-- groepstoeslag per evenement. De migratie wijzigt geen leden of gebruikers.

SET @AEFS_OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT;
SET @AEFS_OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS;
SET @AEFS_OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION;

SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `instellingen` (
    `instelling_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sleutel` VARCHAR(100) NOT NULL,
    `waarde` TEXT NOT NULL,
    `bijgewerkt_door` INT NULL,
    `aangemaakt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `bijgewerkt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`instelling_id`),
    UNIQUE KEY `uq_instellingen_sleutel` (`sleutel`),
    KEY `idx_instellingen_bijgewerkt_door` (`bijgewerkt_door`),
    CONSTRAINT `fk_instellingen_bijgewerkt_door`
        FOREIGN KEY (`bijgewerkt_door`)
        REFERENCES `gebruikers` (`gebruiker_id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `chk_instellingen_sleutel`
        CHECK (CHAR_LENGTH(TRIM(`sleutel`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `instellingen` (`sleutel`, `waarde`, `bijgewerkt_door`)
VALUES
    ('application_name', 'AEFS Eventbeheer', NULL),
    ('organization_name', 'All Events Forever Sure', NULL),
    ('mail_from_name', 'AEFS Eventbeheer', NULL),
    ('mail_reply_to_name', 'AEFS Eventbeheer', NULL),
    ('mail_reply_to_address', '', NULL),
    ('default_shift_compensation', '30.00', NULL),
    ('group_supplement', '10.00', NULL),
    ('default_event_uses_groups', '0', NULL)
ON DUPLICATE KEY UPDATE
    `sleutel` = VALUES(`sleutel`);

DELIMITER $$

DROP PROCEDURE IF EXISTS `aefs_add_event_group_supplement`$$

CREATE PROCEDURE `aefs_add_event_group_supplement`()
main: BEGIN
    DECLARE v_database_name VARCHAR(64) DEFAULT DATABASE();
    DECLARE v_exists INT DEFAULT 0;

    IF v_database_name IS NULL OR v_database_name = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selecteer eerst de AEFS-database voordat de migratie wordt uitgevoerd.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'evenementen'
      AND COLUMN_NAME = 'groepstoeslag_bedrag';

    IF v_exists = 0 THEN
        ALTER TABLE `evenementen`
            ADD COLUMN `groepstoeslag_bedrag` DECIMAL(10, 2) NOT NULL
                DEFAULT 10.00 AFTER `werkt_met_groepen`;
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = v_database_name
      AND TABLE_NAME = 'evenementen'
      AND CONSTRAINT_NAME = 'chk_evenementen_groepstoeslag_bedrag';

    IF v_exists = 0 THEN
        ALTER TABLE `evenementen`
            ADD CONSTRAINT `chk_evenementen_groepstoeslag_bedrag`
                CHECK (`groepstoeslag_bedrag` >= 0);
    END IF;
END$$

CALL `aefs_add_event_group_supplement`()$$
DROP PROCEDURE `aefs_add_event_group_supplement`$$

DELIMITER ;

SET CHARACTER_SET_CLIENT = @AEFS_OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS = @AEFS_OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION = @AEFS_OLD_COLLATION_CONNECTION;
