-- AEFS v2
-- Additive, data-preserving shift compensation reporting support.
-- Existing events and shifts receive the configured defaults; registrations,
-- members, users and historical presence data remain unchanged.

SET @AEFS_OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT;
SET @AEFS_OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS;
SET @AEFS_OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION;

SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS `aefs_add_shift_compensation_reporting`$$

CREATE PROCEDURE `aefs_add_shift_compensation_reporting`()
main: BEGIN
    DECLARE v_database_name VARCHAR(64) DEFAULT DATABASE();
    DECLARE v_exists INT DEFAULT 0;
    DECLARE v_duplicate_members INT DEFAULT 0;

    IF v_database_name IS NULL OR v_database_name = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selecteer eerst de AEFS-database voordat de migratie wordt uitgevoerd.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'evenementen';

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De tabel evenementen bestaat niet.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shifts';

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De tabel shifts bestaat niet.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'leden_groepen';

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De tabel leden_groepen bestaat niet.';
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'evenementen'
      AND COLUMN_NAME = 'werkt_met_groepen';

    IF v_exists = 0 THEN
        ALTER TABLE `evenementen`
            ADD COLUMN `werkt_met_groepen` TINYINT(1) NOT NULL DEFAULT 0
                AFTER `status`;
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = v_database_name
      AND TABLE_NAME = 'evenementen'
      AND CONSTRAINT_NAME = 'chk_evenementen_werkt_met_groepen';

    IF v_exists = 0 THEN
        ALTER TABLE `evenementen`
            ADD CONSTRAINT `chk_evenementen_werkt_met_groepen`
                CHECK (`werkt_met_groepen` IN (0, 1));
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shifts'
      AND COLUMN_NAME = 'vergoeding_bedrag';

    IF v_exists = 0 THEN
        ALTER TABLE `shifts`
            ADD COLUMN `vergoeding_bedrag` DECIMAL(10, 2) NOT NULL DEFAULT 30.00
                AFTER `bijgewerkt_op`;
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = v_database_name
      AND TABLE_NAME = 'shifts'
      AND CONSTRAINT_NAME = 'chk_shifts_vergoeding_bedrag';

    IF v_exists = 0 THEN
        ALTER TABLE `shifts`
            ADD CONSTRAINT `chk_shifts_vergoeding_bedrag`
                CHECK (`vergoeding_bedrag` >= 0);
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shifts'
      AND INDEX_NAME = 'idx_shifts_event_start';

    IF v_exists = 0 THEN
        ALTER TABLE `shifts`
            ADD INDEX `idx_shifts_event_start` (`event_id`, `start_op`);
    END IF;

    SELECT COUNT(*) INTO v_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'leden_groepen'
      AND INDEX_NAME = 'uq_leden_groepen_lid';

    IF v_exists = 0 THEN
        SELECT COUNT(*) INTO v_duplicate_members
        FROM (
            SELECT `lid_id`
            FROM `leden_groepen`
            GROUP BY `lid_id`
            HAVING COUNT(*) > 1
        ) AS duplicate_members;

        IF v_duplicate_members > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'De migratie kan geen exclusieve groepskoppeling instellen: een of meer leden behoren tot meerdere groepen.';
        END IF;

        ALTER TABLE `leden_groepen`
            ADD UNIQUE INDEX `uq_leden_groepen_lid` (`lid_id`);
    END IF;
END$$

CALL `aefs_add_shift_compensation_reporting`()$$
DROP PROCEDURE `aefs_add_shift_compensation_reporting`$$

DELIMITER ;

SET CHARACTER_SET_CLIENT = @AEFS_OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS = @AEFS_OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION = @AEFS_OLD_COLLATION_CONNECTION;
