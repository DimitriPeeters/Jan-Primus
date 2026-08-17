-- AEFS v2
-- Data-preserving migration from the legacy shift tables to the definitive
-- shift_types, shifts and shift_inschrijvingen schema.
--
-- This migration deliberately leaves *_legacy tables in place. MySQL DDL
-- performs implicit commits, so the retained legacy tables are also the
-- recovery source until the migrated data has been verified externally.

SET @AEFS_OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT;
SET @AEFS_OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS;
SET @AEFS_OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION;

SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS `aefs_migrate_legacy_shifts`$$

CREATE PROCEDURE `aefs_migrate_legacy_shifts`()
main: BEGIN
    DECLARE v_database_name VARCHAR(64) DEFAULT DATABASE();
    DECLARE v_has_events INT DEFAULT 0;
    DECLARE v_has_members INT DEFAULT 0;
    DECLARE v_has_users INT DEFAULT 0;
    DECLARE v_has_shift_types INT DEFAULT 0;
    DECLARE v_has_shifts INT DEFAULT 0;
    DECLARE v_has_current_registrations INT DEFAULT 0;
    DECLARE v_has_legacy_shifts INT DEFAULT 0;
    DECLARE v_has_legacy_registrations INT DEFAULT 0;
    DECLARE v_has_legacy_assignments INT DEFAULT 0;
    DECLARE v_has_shift_backup INT DEFAULT 0;
    DECLARE v_has_registration_backup INT DEFAULT 0;
    DECLARE v_has_assignment_backup INT DEFAULT 0;
    DECLARE v_required_columns INT DEFAULT 0;
    DECLARE v_invalid_rows BIGINT DEFAULT 0;
    DECLARE v_source_shift_count BIGINT DEFAULT 0;
    DECLARE v_source_registration_count BIGINT DEFAULT 0;
    DECLARE v_target_shift_count BIGINT DEFAULT 0;
    DECLARE v_target_registration_count BIGINT DEFAULT 0;
    DECLARE v_steward_type_id INT UNSIGNED DEFAULT NULL;
    DECLARE v_migrated_at DATETIME DEFAULT NULL;

    SET v_migrated_at = CURRENT_TIMESTAMP;

    IF v_database_name IS NULL OR v_database_name = '' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selecteer eerst de AEFS-database voordat de shiftmigratie wordt uitgevoerd.';
    END IF;

    SELECT COUNT(*) INTO v_has_events
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'evenementen';

    SELECT COUNT(*) INTO v_has_members
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'leden';

    SELECT COUNT(*) INTO v_has_users
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'gebruikers';

    IF v_has_events = 0 OR v_has_members = 0 OR v_has_users = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De tabellen evenementen, leden en gebruikers moeten bestaan vóór de shiftmigratie.';
    END IF;

    SELECT COUNT(*) INTO v_has_shift_types
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shift_types';

    IF v_has_shift_types = 0 THEN
        CREATE TABLE `shift_types` (
            `type_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `naam` VARCHAR(100) COLLATE utf8mb4_general_ci NOT NULL,
            `kleur` VARCHAR(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#1E3A8A',
            `icoon` VARCHAR(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
            `omschrijving` TEXT COLLATE utf8mb4_general_ci,
            `actief` TINYINT(1) NOT NULL DEFAULT 1,
            `aangemaakt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `bijgewerkt_op` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`type_id`),
            UNIQUE KEY `uq_shift_types_naam` (`naam`),
            KEY `idx_shift_types_actief` (`actief`)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_general_ci;
    ELSE
        SELECT COUNT(*) INTO v_required_columns
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = v_database_name
          AND TABLE_NAME = 'shift_types'
          AND COLUMN_NAME IN (
              'type_id',
              'naam',
              'kleur',
              'icoon',
              'omschrijving',
              'actief',
              'aangemaakt_op',
              'bijgewerkt_op'
          );

        IF v_required_columns <> 8 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'De bestaande tabel shift_types heeft niet het verwachte AEFS-schema.';
        END IF;
    END IF;

    INSERT INTO `shift_types` (
        `naam`,
        `kleur`,
        `icoon`,
        `omschrijving`,
        `actief`,
        `aangemaakt_op`,
        `bijgewerkt_op`
    )
    SELECT
        'Steward',
        '#1E3A8A',
        'users',
        NULL,
        1,
        v_migrated_at,
        NULL
    WHERE NOT EXISTS (
        SELECT 1
        FROM `shift_types`
        WHERE `naam` = 'Steward'
    );

    SELECT `type_id` INTO v_steward_type_id
    FROM `shift_types`
    WHERE `naam` = 'Steward'
    ORDER BY `type_id`
    LIMIT 1;

    IF v_steward_type_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Het shifttype Steward kon niet worden gevonden of aangemaakt.';
    END IF;

    SELECT COUNT(*) INTO v_has_shifts
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shifts';

    SELECT COUNT(*) INTO v_has_current_registrations
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shift_inschrijvingen'
      AND COLUMN_NAME = 'inschrijving_id';

    SELECT COUNT(*) INTO v_has_legacy_shifts
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_shifts';

    SELECT COUNT(*) INTO v_has_legacy_registrations
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shift_inschrijvingen'
      AND COLUMN_NAME = 'id';

    SELECT COUNT(*) INTO v_has_legacy_assignments
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shift_toewijzingen';

    SELECT COUNT(*) INTO v_has_shift_backup
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'event_shifts_legacy';

    SELECT COUNT(*) INTO v_has_registration_backup
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shift_inschrijvingen_legacy';

    SELECT COUNT(*) INTO v_has_assignment_backup
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = v_database_name
      AND TABLE_NAME = 'shift_toewijzingen_legacy';

    IF v_has_shifts > 0 THEN
        IF v_has_current_registrations = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'De tabel shifts bestaat, maar shift_inschrijvingen heeft niet het definitieve schema.';
        END IF;

        SELECT COUNT(*) INTO v_required_columns
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = v_database_name
          AND TABLE_NAME = 'shifts'
          AND COLUMN_NAME IN (
              'shift_id',
              'event_id',
              'type_id',
              'naam',
              'start_op',
              'eind_op',
              'max_personen',
              'status',
              'aangemaakt_op',
              'bijgewerkt_op'
          );

        IF v_required_columns <> 10 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'De bestaande tabel shifts heeft niet het definitieve AEFS-schema.';
        END IF;

        SELECT COUNT(*) INTO v_required_columns
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = v_database_name
          AND TABLE_NAME = 'shift_inschrijvingen'
          AND COLUMN_NAME IN (
              'inschrijving_id',
              'shift_id',
              'lid_id',
              'status',
              'opmerking_lid',
              'goedgekeurd_door',
              'goedgekeurd_op',
              'geannuleerd_door',
              'geannuleerd_op',
              'annulatie_reden',
              'aanwezig',
              'aanwezig_afgevinkt_op',
              'aangemaakt_op',
              'bijgewerkt_op'
          );

        IF v_required_columns <> 14 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'De bestaande tabel shift_inschrijvingen heeft niet het definitieve AEFS-schema.';
        END IF;

        IF v_has_legacy_shifts > 0
           OR v_has_legacy_registrations > 0
           OR v_has_legacy_assignments > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Definitieve en actieve legacy-shifttabellen bestaan gelijktijdig; handmatige controle is vereist.';
        END IF;
    ELSE
        IF v_has_legacy_shifts > 0 THEN
            SELECT COUNT(*) INTO v_invalid_rows
            FROM `event_shifts` es
            LEFT JOIN `evenementen` e
                ON e.`event_id` = es.`event_id`
            WHERE e.`event_id` IS NULL
               OR es.`shift_datum` IS NULL
               OR es.`starttijd` IS NULL
               OR es.`eindtijd` IS NULL
               OR es.`max_personen` <= 0;

            IF v_invalid_rows > 0 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Legacyshifts bevatten ongeldige evenementen, tijden, datums of capaciteiten.';
            END IF;

            SELECT COUNT(*) INTO v_source_shift_count
            FROM `event_shifts`;

            IF v_has_shift_backup > 0 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'event_shifts en event_shifts_legacy bestaan beide; de migratie overschrijft geen bestaande backup.';
            END IF;
        END IF;

        IF v_has_legacy_registrations > 0 THEN
            SELECT COUNT(*) INTO v_invalid_rows
            FROM `shift_inschrijvingen` si
            LEFT JOIN `event_shifts` es
                ON es.`shift_id` = si.`shift_id`
            LEFT JOIN `leden` l
                ON l.`lid_id` = si.`lid_id`
            WHERE es.`shift_id` IS NULL
               OR l.`lid_id` IS NULL;

            IF v_invalid_rows > 0 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Legacy shiftinschrijvingen bevatten verweesde shift- of ledenrelaties.';
            END IF;

            SELECT COUNT(*) INTO v_invalid_rows
            FROM (
                SELECT `shift_id`, `lid_id`
                FROM `shift_inschrijvingen`
                GROUP BY `shift_id`, `lid_id`
                HAVING COUNT(*) > 1
            ) duplicates;

            IF v_invalid_rows > 0 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Legacy shiftinschrijvingen bevatten dubbele combinaties van shift en lid.';
            END IF;

            SELECT COUNT(*) INTO v_source_registration_count
            FROM `shift_inschrijvingen`;

            IF v_has_registration_backup > 0 THEN
                SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'shift_inschrijvingen en shift_inschrijvingen_legacy bestaan beide; de migratie overschrijft geen bestaande backup.';
            END IF;
        ELSEIF v_has_current_registrations > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Definitieve shift_inschrijvingen bestaat zonder de tabel shifts.';
        END IF;

        IF v_has_legacy_assignments > 0 AND v_has_assignment_backup > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'shift_toewijzingen en shift_toewijzingen_legacy bestaan beide; de migratie overschrijft geen bestaande backup.';
        END IF;

        IF v_has_legacy_shifts > 0 THEN
            SET @aefs_rename_sql = 'RENAME TABLE `event_shifts` TO `event_shifts_legacy`';

            IF v_has_legacy_registrations > 0 THEN
                SET @aefs_rename_sql = CONCAT(
                    @aefs_rename_sql,
                    ', `shift_inschrijvingen` TO `shift_inschrijvingen_legacy`'
                );
            END IF;

            IF v_has_legacy_assignments > 0 THEN
                SET @aefs_rename_sql = CONCAT(
                    @aefs_rename_sql,
                    ', `shift_toewijzingen` TO `shift_toewijzingen_legacy`'
                );
            END IF;

            PREPARE aefs_rename_statement FROM @aefs_rename_sql;
            EXECUTE aefs_rename_statement;
            DEALLOCATE PREPARE aefs_rename_statement;

            SET v_has_shift_backup = 1;
            SET v_has_registration_backup = v_has_legacy_registrations;
            SET v_has_assignment_backup = v_has_legacy_assignments;
        ELSEIF v_has_legacy_registrations > 0 OR v_has_legacy_assignments > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Legacy inschrijvings- of toewijzingstabellen bestaan zonder event_shifts.';
        END IF;

        CREATE TABLE `shifts` (
            `shift_id` INT NOT NULL AUTO_INCREMENT,
            `event_id` INT NOT NULL,
            `type_id` INT UNSIGNED NOT NULL,
            `naam` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
            `start_op` DATETIME NOT NULL,
            `eind_op` DATETIME NOT NULL,
            `max_personen` INT UNSIGNED NOT NULL DEFAULT 1,
            `status` ENUM('actief', 'geannuleerd') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'actief',
            `aangemaakt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `bijgewerkt_op` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`shift_id`),
            KEY `idx_shifts_event` (`event_id`),
            KEY `idx_shifts_type` (`type_id`),
            KEY `idx_shifts_start` (`start_op`),
            KEY `idx_shifts_status` (`status`),
            CONSTRAINT `fk_shifts_event`
                FOREIGN KEY (`event_id`)
                REFERENCES `evenementen` (`event_id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE,
            CONSTRAINT `fk_shifts_type`
                FOREIGN KEY (`type_id`)
                REFERENCES `shift_types` (`type_id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE,
            CONSTRAINT `chk_shifts_capacity`
                CHECK (`max_personen` > 0),
            CONSTRAINT `chk_shifts_period`
                CHECK (`eind_op` > `start_op`)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_general_ci;

        CREATE TABLE `shift_inschrijvingen` (
            `inschrijving_id` INT NOT NULL AUTO_INCREMENT,
            `shift_id` INT NOT NULL,
            `lid_id` INT NOT NULL,
            `status` ENUM(
                'wachtend',
                'bevestigd',
                'reserve',
                'geweigerd',
                'geannuleerd'
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'wachtend',
            `opmerking_lid` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
            `goedgekeurd_door` INT DEFAULT NULL,
            `goedgekeurd_op` DATETIME DEFAULT NULL,
            `geannuleerd_door` INT DEFAULT NULL,
            `geannuleerd_op` DATETIME DEFAULT NULL,
            `annulatie_reden` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
            `aanwezig` TINYINT(1) NOT NULL DEFAULT 0,
            `aanwezig_afgevinkt_op` DATETIME DEFAULT NULL,
            `aangemaakt_op` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `bijgewerkt_op` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`inschrijving_id`),
            UNIQUE KEY `uq_shift_inschrijvingen_shift_lid` (`shift_id`, `lid_id`),
            KEY `idx_shift_inschrijvingen_lid` (`lid_id`),
            KEY `idx_shift_inschrijvingen_status` (`status`),
            KEY `idx_shift_inschrijvingen_goedgekeurd_door` (`goedgekeurd_door`),
            KEY `idx_shift_inschrijvingen_geannuleerd_door` (`geannuleerd_door`),
            CONSTRAINT `fk_shift_inschrijvingen_geannuleerd_door`
                FOREIGN KEY (`geannuleerd_door`)
                REFERENCES `gebruikers` (`gebruiker_id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE,
            CONSTRAINT `fk_shift_inschrijvingen_goedgekeurd_door`
                FOREIGN KEY (`goedgekeurd_door`)
                REFERENCES `gebruikers` (`gebruiker_id`)
                ON DELETE SET NULL
                ON UPDATE CASCADE,
            CONSTRAINT `fk_shift_inschrijvingen_lid`
                FOREIGN KEY (`lid_id`)
                REFERENCES `leden` (`lid_id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_shift_inschrijvingen_shift`
                FOREIGN KEY (`shift_id`)
                REFERENCES `shifts` (`shift_id`)
                ON DELETE RESTRICT
                ON UPDATE CASCADE
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_general_ci;

        IF v_has_shift_backup > 0 THEN
            INSERT INTO `shifts` (
                `shift_id`,
                `event_id`,
                `type_id`,
                `naam`,
                `start_op`,
                `eind_op`,
                `max_personen`,
                `status`,
                `aangemaakt_op`,
                `bijgewerkt_op`
            )
            SELECT
                es.`shift_id`,
                es.`event_id`,
                v_steward_type_id,
                NULLIF(TRIM(es.`naam`), ''),
                TIMESTAMP(es.`shift_datum`, es.`starttijd`),
                CASE
                    WHEN es.`eindtijd` <= es.`starttijd`
                        THEN TIMESTAMP(es.`shift_datum`, es.`eindtijd`) + INTERVAL 1 DAY
                    ELSE TIMESTAMP(es.`shift_datum`, es.`eindtijd`)
                END,
                es.`max_personen`,
                'actief',
                v_migrated_at,
                NULL
            FROM `event_shifts_legacy` es
            ORDER BY es.`shift_id`;
        END IF;

        IF v_has_registration_backup > 0 THEN
            INSERT INTO `shift_inschrijvingen` (
                `inschrijving_id`,
                `shift_id`,
                `lid_id`,
                `status`,
                `opmerking_lid`,
                `goedgekeurd_door`,
                `goedgekeurd_op`,
                `geannuleerd_door`,
                `geannuleerd_op`,
                `annulatie_reden`,
                `aanwezig`,
                `aanwezig_afgevinkt_op`,
                `aangemaakt_op`,
                `bijgewerkt_op`
            )
            SELECT
                si.`id`,
                si.`shift_id`,
                si.`lid_id`,
                'bevestigd',
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                si.`aanwezig`,
                si.`aanwezig_afgevinkt_op`,
                COALESCE(si.`aangemaakt_op`, v_migrated_at),
                NULL
            FROM `shift_inschrijvingen_legacy` si
            ORDER BY si.`id`;
        END IF;

        SELECT COUNT(*) INTO v_target_shift_count
        FROM `shifts`;

        SELECT COUNT(*) INTO v_target_registration_count
        FROM `shift_inschrijvingen`;

        IF v_has_shift_backup > 0
           AND v_target_shift_count <> v_source_shift_count THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Het aantal gemigreerde shifts komt niet overeen met de legacybron.';
        END IF;

        IF v_has_registration_backup > 0
           AND v_target_registration_count <> v_source_registration_count THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Het aantal gemigreerde shiftinschrijvingen komt niet overeen met de legacybron.';
        END IF;
    END IF;

    SELECT COUNT(*) INTO v_invalid_rows
    FROM `shifts` s
    LEFT JOIN `evenementen` e
        ON e.`event_id` = s.`event_id`
    LEFT JOIN `shift_types` st
        ON st.`type_id` = s.`type_id`
    WHERE e.`event_id` IS NULL
       OR st.`type_id` IS NULL
       OR s.`eind_op` <= s.`start_op`
       OR s.`max_personen` <= 0;

    IF v_invalid_rows > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De definitieve shifts bevatten ongeldige relaties, perioden of capaciteiten.';
    END IF;

    SELECT COUNT(*) INTO v_invalid_rows
    FROM `shift_inschrijvingen` si
    LEFT JOIN `shifts` s
        ON s.`shift_id` = si.`shift_id`
    LEFT JOIN `leden` l
        ON l.`lid_id` = si.`lid_id`
    LEFT JOIN `gebruikers` gu
        ON gu.`gebruiker_id` = si.`goedgekeurd_door`
    LEFT JOIN `gebruikers` au
        ON au.`gebruiker_id` = si.`geannuleerd_door`
    WHERE s.`shift_id` IS NULL
       OR l.`lid_id` IS NULL
       OR (
           si.`goedgekeurd_door` IS NOT NULL
           AND gu.`gebruiker_id` IS NULL
       )
       OR (
           si.`geannuleerd_door` IS NOT NULL
           AND au.`gebruiker_id` IS NULL
       );

    IF v_invalid_rows > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De definitieve shiftinschrijvingen bevatten verweesde relaties.';
    END IF;

    SELECT COUNT(*) INTO v_invalid_rows
    FROM (
        SELECT `shift_id`, `lid_id`
        FROM `shift_inschrijvingen`
        GROUP BY `shift_id`, `lid_id`
        HAVING COUNT(*) > 1
    ) duplicates;

    IF v_invalid_rows > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'De definitieve shiftinschrijvingen bevatten dubbele shift/lid-combinaties.';
    END IF;

    SELECT COUNT(DISTINCT CONCAT(TABLE_NAME, ':', INDEX_NAME))
    INTO v_required_columns
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = v_database_name
      AND (
          (TABLE_NAME = 'shift_types' AND INDEX_NAME IN (
              'PRIMARY',
              'uq_shift_types_naam',
              'idx_shift_types_actief'
          ))
          OR (TABLE_NAME = 'shifts' AND INDEX_NAME IN (
              'PRIMARY',
              'idx_shifts_event',
              'idx_shifts_type',
              'idx_shifts_start',
              'idx_shifts_status'
          ))
          OR (TABLE_NAME = 'shift_inschrijvingen' AND INDEX_NAME IN (
              'PRIMARY',
              'uq_shift_inschrijvingen_shift_lid',
              'idx_shift_inschrijvingen_lid',
              'idx_shift_inschrijvingen_status',
              'idx_shift_inschrijvingen_goedgekeurd_door',
              'idx_shift_inschrijvingen_geannuleerd_door'
          ))
      );

    IF v_required_columns <> 14 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Een of meer definitieve shiftindexen ontbreken.';
    END IF;

    SELECT COUNT(*) INTO v_required_columns
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = v_database_name
      AND CONSTRAINT_NAME IN (
          'fk_shifts_event',
          'fk_shifts_type',
          'fk_shift_inschrijvingen_shift',
          'fk_shift_inschrijvingen_lid',
          'fk_shift_inschrijvingen_goedgekeurd_door',
          'fk_shift_inschrijvingen_geannuleerd_door'
      );

    IF v_required_columns <> 6 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Een of meer definitieve shift-foreign keys ontbreken.';
    END IF;

    SELECT COUNT(*) INTO v_required_columns
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = v_database_name
      AND TABLE_NAME = 'shifts'
      AND CONSTRAINT_TYPE = 'CHECK'
      AND CONSTRAINT_NAME IN (
          'chk_shifts_capacity',
          'chk_shifts_period'
      );

    IF v_required_columns <> 2 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Een of meer definitieve shift-checkconstraints ontbreken.';
    END IF;

    SELECT
        (SELECT COUNT(*) FROM `shift_types`) AS `shift_types_count`,
        (SELECT COUNT(*) FROM `shifts`) AS `shifts_count`,
        (SELECT COUNT(*) FROM `shift_inschrijvingen`) AS `shift_inschrijvingen_count`;

    SELECT `status`, COUNT(*) AS `aantal`
    FROM `shift_inschrijvingen`
    GROUP BY `status`
    ORDER BY `status`;
END$$

CALL `aefs_migrate_legacy_shifts`()$$
DROP PROCEDURE `aefs_migrate_legacy_shifts`$$

DELIMITER ;

SET CHARACTER_SET_CLIENT = @AEFS_OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS = @AEFS_OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION = @AEFS_OLD_COLLATION_CONNECTION;
