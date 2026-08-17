-- AEFS v2
-- Additive, data-preserving event-registration cancellation workflow.
-- Existing event registrations and all member/user data remain unchanged.

SET @AEFS_OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT;
SET @AEFS_OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS;
SET @AEFS_OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION;

SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS `aefs_add_event_cancellation_workflow`$$

CREATE PROCEDURE `aefs_add_event_cancellation_workflow`()
main: BEGIN
    DECLARE v_database_name VARCHAR(64) DEFAULT DATABASE();
    DECLARE v_exists INT DEFAULT 0;

    IF v_database_name IS NULL OR v_database_name = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selecteer eerst de AEFS-database voordat de migratie wordt uitgevoerd.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_inschrijvingen';

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De tabel event_inschrijvingen bestaat niet.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'gebruikers';

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De tabel gebruikers bestaat niet.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_inschrijvingen'
      AND COLUMN_NAME = 'annulatie_aangevraagd_op';

    IF v_exists = 0 THEN
        ALTER TABLE `event_inschrijvingen`
            ADD COLUMN `annulatie_aangevraagd_op` DATETIME NULL
                AFTER `uitschrijfreden`;
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_inschrijvingen'
      AND COLUMN_NAME = 'annulatie_bevestigd_door';

    IF v_exists = 0 THEN
        ALTER TABLE `event_inschrijvingen`
            ADD COLUMN `annulatie_bevestigd_door` INT NULL
                AFTER `uitgeschreven_op`;
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_inschrijvingen'
      AND INDEX_NAME = 'idx_event_inschrijvingen_annulatie_open';

    IF v_exists = 0 THEN
        ALTER TABLE `event_inschrijvingen`
            ADD INDEX `idx_event_inschrijvingen_annulatie_open`
                (`annulatie_aangevraagd_op`, `uitgeschreven_op`);
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_inschrijvingen'
      AND INDEX_NAME = 'idx_event_inschrijvingen_annulatie_bevestigd_door';

    IF v_exists = 0 THEN
        ALTER TABLE `event_inschrijvingen`
            ADD INDEX `idx_event_inschrijvingen_annulatie_bevestigd_door`
                (`annulatie_bevestigd_door`);
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_inschrijvingen'
      AND CONSTRAINT_NAME = 'fk_event_inschrijvingen_annulatie_bevestigd_door';

    IF v_exists = 0 THEN
        ALTER TABLE `event_inschrijvingen`
            ADD CONSTRAINT `fk_event_inschrijvingen_annulatie_bevestigd_door`
                FOREIGN KEY (`annulatie_bevestigd_door`)
                REFERENCES `gebruikers` (`gebruiker_id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE;
    END IF;

END$$

CALL `aefs_add_event_cancellation_workflow`()$$
DROP PROCEDURE `aefs_add_event_cancellation_workflow`$$

DELIMITER ;

SET CHARACTER_SET_CLIENT = @AEFS_OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS = @AEFS_OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION = @AEFS_OLD_COLLATION_CONNECTION;
