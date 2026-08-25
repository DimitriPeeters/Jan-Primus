-- Jan Primus Ledenbeheer
-- Additief installatieschema voor een gedeelde One.com/WordPress-database.
-- Voer vooraf bin/check-shared-database.php uit.
-- Dit bestand bevat bewust geen DROP, TRUNCATE, CREATE DATABASE of USE.

SET NAMES utf8mb4;
SET @JP_OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `leden` (
  `lid_id` int NOT NULL AUTO_INCREMENT,
  `voornaam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `achternaam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `actief` tinyint(1) NOT NULL DEFAULT '1',
  `straat` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `postcode` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `gemeente` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `land` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'België',
  `telefoon` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `geboortedatum` date NOT NULL,
  `geslacht` enum('M','V','X') COLLATE utf8mb4_general_ci NOT NULL,
  `opmerkingen` text COLLATE utf8mb4_general_ci,
  `gdpr_consent` tinyint(1) NOT NULL DEFAULT '0',
  `gdpr_timestamp` datetime DEFAULT NULL,
  `toegetreden_op` date DEFAULT NULL,
  `uitgetreden_op` date DEFAULT NULL,
  `aangemaakt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rijksregisternummer` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `tshirtmaat` enum('XS','S','M','L','XL','XXL') COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`lid_id`),
  CONSTRAINT `chk_leden_lidmaatschapsperiode`
    CHECK (`uitgetreden_op` IS NULL OR `toegetreden_op` IS NULL OR `uitgetreden_op` >= `toegetreden_op`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `gebruikers` (
  `gebruiker_id` int NOT NULL AUTO_INCREMENT,
  `lid_id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `wachtwoord_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rol` enum('lid','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'lid',
  `goedkeuringsstatus` enum('wachtend','goedgekeurd') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'wachtend',
  `goedgekeurd_op` datetime DEFAULT NULL,
  `actief` tinyint(1) NOT NULL DEFAULT '1',
  `aangemaakt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `wachtwoord_moet_wijzigen` tinyint(1) NOT NULL DEFAULT '1',
  `reset_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `mail_blacklist` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gebruiker_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uq_gebruikers_lid` (`lid_id`),
  CONSTRAINT `fk_gebruiker_lid` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `audit_id` bigint NOT NULL AUTO_INCREMENT,
  `entity` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int NOT NULL,
  `action` enum('create','update','delete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `idx_entity` (`entity`),
  KEY `idx_entity_id` (`entity_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `instellingen` (
  `instelling_id` int unsigned NOT NULL AUTO_INCREMENT,
  `sleutel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `waarde` text COLLATE utf8mb4_general_ci NOT NULL,
  `bijgewerkt_door` int DEFAULT NULL,
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`instelling_id`),
  UNIQUE KEY `uq_instellingen_sleutel` (`sleutel`),
  KEY `idx_instellingen_bijgewerkt_door` (`bijgewerkt_door`),
  CONSTRAINT `fk_instellingen_bijgewerkt_door` FOREIGN KEY (`bijgewerkt_door`) REFERENCES `gebruikers` (`gebruiker_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_instellingen_sleutel` CHECK ((char_length(trim(`sleutel`)) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `evenementen` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `titel` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `beschrijving` text COLLATE utf8mb4_general_ci,
  `locatie` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_deelnemers` int DEFAULT NULL,
  `aangemaakt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL,
  `startdatum` date NOT NULL,
  `einddatum` date DEFAULT NULL,
  `planning_verstuurd` datetime DEFAULT NULL,
  `status` enum('concept','gepubliceerd','afgesloten','geannuleerd') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'concept',
  PRIMARY KEY (`event_id`),
  KEY `idx_evenementen_status_startdatum` (`status`,`startdatum`),
  KEY `idx_evenementen_einddatum` (`einddatum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `event_inschrijvingen` (
  `inschrijving_id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `lid_id` int NOT NULL,
  `status` enum('wachtend','bevestigd','reserve','geweigerd') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'wachtend',
  `aangemeld_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uitschrijfreden` text COLLATE utf8mb4_general_ci,
  `annulatie_aangevraagd_op` datetime DEFAULT NULL,
  `uitgeschreven_op` datetime DEFAULT NULL,
  `annulatie_bevestigd_door` int DEFAULT NULL,
  PRIMARY KEY (`inschrijving_id`),
  UNIQUE KEY `event_id` (`event_id`,`lid_id`),
  KEY `lid_id` (`lid_id`),
  KEY `idx_event_inschrijvingen_annulatie_open` (`annulatie_aangevraagd_op`,`uitgeschreven_op`),
  KEY `idx_event_inschrijvingen_annulatie_bevestigd_door` (`annulatie_bevestigd_door`),
  CONSTRAINT `event_inschrijvingen_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `evenementen` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `event_inschrijvingen_ibfk_2` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_inschrijvingen_annulatie_bevestigd_door` FOREIGN KEY (`annulatie_bevestigd_door`) REFERENCES `gebruikers` (`gebruiker_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `event_inschrijving_dagen` (
  `inschrijving_dag_id` int NOT NULL AUTO_INCREMENT,
  `inschrijving_id` int NOT NULL,
  `datum` date NOT NULL,
  PRIMARY KEY (`inschrijving_dag_id`),
  KEY `inschrijving_id` (`inschrijving_id`),
  CONSTRAINT `event_inschrijving_dagen_ibfk_1` FOREIGN KEY (`inschrijving_id`) REFERENCES `event_inschrijvingen` (`inschrijving_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `shift_types` (
  `type_id` int unsigned NOT NULL AUTO_INCREMENT,
  `naam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kleur` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#EF6012',
  `icoon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `omschrijving` text COLLATE utf8mb4_general_ci,
  `actief` tinyint(1) NOT NULL DEFAULT '1',
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uq_shift_types_naam` (`naam`),
  KEY `idx_shift_types_actief` (`actief`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `shifts` (
  `shift_id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `type_id` int unsigned NOT NULL,
  `naam` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_op` datetime NOT NULL,
  `eind_op` datetime NOT NULL,
  `max_personen` int unsigned NOT NULL DEFAULT '1',
  `status` enum('actief','geannuleerd') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'actief',
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`shift_id`),
  KEY `idx_shifts_event` (`event_id`),
  KEY `idx_shifts_type` (`type_id`),
  KEY `idx_shifts_start` (`start_op`),
  KEY `idx_shifts_status` (`status`),
  KEY `idx_shifts_event_start` (`event_id`,`start_op`),
  CONSTRAINT `fk_shifts_event` FOREIGN KEY (`event_id`) REFERENCES `evenementen` (`event_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_shifts_type` FOREIGN KEY (`type_id`) REFERENCES `shift_types` (`type_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_shifts_capacity` CHECK ((`max_personen` > 0)),
  CONSTRAINT `chk_shifts_period` CHECK ((`eind_op` > `start_op`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `shift_inschrijvingen` (
  `inschrijving_id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `lid_id` int NOT NULL,
  `status` enum('wachtend','bevestigd','reserve','geweigerd','geannuleerd') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'wachtend',
  `opmerking_lid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `goedgekeurd_door` int DEFAULT NULL,
  `goedgekeurd_op` datetime DEFAULT NULL,
  `geannuleerd_door` int DEFAULT NULL,
  `geannuleerd_op` datetime DEFAULT NULL,
  `annulatie_reden` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `aanwezig` tinyint(1) NOT NULL DEFAULT '0',
  `aanwezig_afgevinkt_op` datetime DEFAULT NULL,
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inschrijving_id`),
  UNIQUE KEY `uq_shift_inschrijvingen_shift_lid` (`shift_id`,`lid_id`),
  KEY `idx_shift_inschrijvingen_lid` (`lid_id`),
  KEY `idx_shift_inschrijvingen_status` (`status`),
  KEY `idx_shift_inschrijvingen_goedgekeurd_door` (`goedgekeurd_door`),
  KEY `idx_shift_inschrijvingen_geannuleerd_door` (`geannuleerd_door`),
  CONSTRAINT `fk_shift_inschrijvingen_geannuleerd_door` FOREIGN KEY (`geannuleerd_door`) REFERENCES `gebruikers` (`gebruiker_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_shift_inschrijvingen_goedgekeurd_door` FOREIGN KEY (`goedgekeurd_door`) REFERENCES `gebruikers` (`gebruiker_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_shift_inschrijvingen_lid` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_shift_inschrijvingen_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`shift_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mailings` (
  `mailing_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `doelgroep_type` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `doelgroep_json` json DEFAULT NULL,
  `event_id` int DEFAULT NULL,
  `aangemaakt_door` int DEFAULT NULL,
  `onderwerp` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `inhoud_html` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `inhoud_tekst` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('in_wachtrij','bezig','verzonden','gedeeltelijk_mislukt','mislukt') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'in_wachtrij',
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `voltooid_op` datetime DEFAULT NULL,
  PRIMARY KEY (`mailing_id`),
  KEY `idx_mailings_status_created` (`status`,`aangemaakt_op`),
  KEY `idx_mailings_event` (`event_id`),
  KEY `idx_mailings_creator` (`aangemaakt_door`),
  CONSTRAINT `fk_mailings_creator` FOREIGN KEY (`aangemaakt_door`) REFERENCES `gebruikers` (`gebruiker_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mailings_event` FOREIGN KEY (`event_id`) REFERENCES `evenementen` (`event_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mailing_bijlagen` (
  `bijlage_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mailing_id` bigint unsigned NOT NULL,
  `originele_naam` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `opslagpad` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `bestandsgrootte` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_general_ci NOT NULL,
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bijlage_id`),
  KEY `idx_mailing_bijlagen_mailing` (`mailing_id`),
  CONSTRAINT `fk_mailing_bijlagen_mailing` FOREIGN KEY (`mailing_id`) REFERENCES `mailings` (`mailing_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_mailing_bijlagen_grootte` CHECK ((`bestandsgrootte` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `mailing_ontvangers` (
  `ontvanger_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mailing_id` bigint unsigned NOT NULL,
  `lid_id` int DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `naam` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `onderwerp` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `inhoud_html` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `inhoud_tekst` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('in_wachtrij','bezig','verzonden','mislukt') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'in_wachtrij',
  `pogingen` tinyint unsigned NOT NULL DEFAULT '0',
  `volgende_poging_op` datetime DEFAULT NULL,
  `vergrendeld_op` datetime DEFAULT NULL,
  `verzonden_op` datetime DEFAULT NULL,
  `message_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foutmelding` text COLLATE utf8mb4_general_ci,
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ontvanger_id`),
  UNIQUE KEY `uq_mailing_ontvanger_email` (`mailing_id`,`email`),
  KEY `idx_mailing_ontvangers_queue` (`status`,`volgende_poging_op`,`vergrendeld_op`),
  KEY `idx_mailing_ontvangers_mailing_status` (`mailing_id`,`status`),
  KEY `idx_mailing_ontvangers_lid` (`lid_id`),
  CONSTRAINT `fk_mailing_ontvangers_lid` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mailing_ontvangers_mailing` FOREIGN KEY (`mailing_id`) REFERENCES `mailings` (`mailing_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ledenbeheer_migraties` (
  `migratie` varchar(190) NOT NULL,
  `uitgevoerd_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`migratie`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `instellingen` (`sleutel`, `waarde`, `bijgewerkt_door`)
VALUES
  ('application_name', 'Ledenbeheer', NULL),
  ('organization_name', 'vzw Jan Primus', NULL),
  ('mail_from_name', 'vzw Jan Primus', NULL),
  ('mail_reply_to_name', 'vzw Jan Primus', NULL),
  ('mail_reply_to_address', 'info@jan-primus.be', NULL)
ON DUPLICATE KEY UPDATE `sleutel` = VALUES(`sleutel`);

INSERT INTO `shift_types` (`naam`, `kleur`, `icoon`, `omschrijving`, `actief`)
VALUES ('Steward', '#EF6012', 'user-check', 'Standaardfunctie voor medewerkers', 1)
ON DUPLICATE KEY UPDATE `naam` = VALUES(`naam`);

INSERT INTO ledenbeheer_migraties (migratie)
VALUES ('20260825_000000_initial_shared_database')
ON DUPLICATE KEY UPDATE `migratie` = VALUES(`migratie`);

SET FOREIGN_KEY_CHECKS = @JP_OLD_FOREIGN_KEY_CHECKS;
