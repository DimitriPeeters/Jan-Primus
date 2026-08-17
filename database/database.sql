
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
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
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `betalingen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `betalingen` (
  `betaling_id` int NOT NULL AUTO_INCREMENT,
  `lid_id` int NOT NULL,
  `jaar` int NOT NULL,
  `bedrag` decimal(10,2) NOT NULL,
  `betaald_op` date NOT NULL,
  `methode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`betaling_id`),
  UNIQUE KEY `lid_id` (`lid_id`,`jaar`),
  CONSTRAINT `fk_betaling_lid` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_berichten`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_berichten` (
  `bericht_id` int NOT NULL AUTO_INCREMENT,
  `naam` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `bericht` text COLLATE utf8mb4_general_ci NOT NULL,
  `gdpr_consent` tinyint(1) NOT NULL,
  `consent_timestamp` datetime NOT NULL,
  `ip_adres` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aangemaakt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bericht_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evenementen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evenementen` (
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
  `werkt_met_groepen` tinyint(1) NOT NULL DEFAULT '0',
  `groepstoeslag_bedrag` decimal(10,2) NOT NULL DEFAULT '10.00',
  PRIMARY KEY (`event_id`),
  KEY `idx_evenementen_status_startdatum` (`status`,`startdatum`),
  KEY `idx_evenementen_einddatum` (`einddatum`),
  CONSTRAINT `chk_evenementen_groepstoeslag_bedrag` CHECK ((`groepstoeslag_bedrag` >= 0)),
  CONSTRAINT `chk_evenementen_werkt_met_groepen` CHECK ((`werkt_met_groepen` in (0,1)))
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_inschrijving_dagen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_inschrijving_dagen` (
  `inschrijving_dag_id` int NOT NULL AUTO_INCREMENT,
  `inschrijving_id` int NOT NULL,
  `datum` date NOT NULL,
  PRIMARY KEY (`inschrijving_dag_id`),
  KEY `inschrijving_id` (`inschrijving_id`),
  CONSTRAINT `event_inschrijving_dagen_ibfk_1` FOREIGN KEY (`inschrijving_id`) REFERENCES `event_inschrijvingen` (`inschrijving_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=278 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_inschrijvingen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_inschrijvingen` (
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
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_shifts_legacy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_shifts_legacy` (
  `shift_id` int NOT NULL AUTO_INCREMENT,
  `event_id` int NOT NULL,
  `shift_datum` date NOT NULL,
  `naam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `starttijd` time DEFAULT NULL,
  `eindtijd` time DEFAULT NULL,
  `max_personen` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`shift_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `event_shifts_legacy_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `evenementen` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gebruikers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gebruikers` (
  `gebruiker_id` int NOT NULL AUTO_INCREMENT,
  `lid_id` int DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `wachtwoord_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rol` enum('lid','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'lid',
  `goedkeuringsstatus` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'goedgekeurd',
  `goedgekeurd_op` datetime DEFAULT NULL,
  `actief` tinyint(1) NOT NULL DEFAULT '1',
  `aangemaakt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `wachtwoord_moet_wijzigen` tinyint(1) NOT NULL DEFAULT '1',
  `reset_token` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `mail_blacklist` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gebruiker_id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_gebruiker_lid` (`lid_id`),
  CONSTRAINT `fk_gebruiker_lid` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groepen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groepen` (
  `groep_id` int NOT NULL AUTO_INCREMENT,
  `naam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `beschrijving` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`groep_id`),
  UNIQUE KEY `naam` (`naam`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `instellingen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instellingen` (
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leden` (
  `lid_id` int NOT NULL AUTO_INCREMENT,
  `voornaam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `achternaam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `actief` tinyint(1) NOT NULL DEFAULT '1',
  `straat` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postcode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gemeente` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `land` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefoon` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `geboortedatum` date DEFAULT NULL,
  `geslacht` enum('M','V','X') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `opmerkingen` text COLLATE utf8mb4_general_ci,
  `gdpr_consent` tinyint(1) NOT NULL DEFAULT '0',
  `gdpr_timestamp` datetime DEFAULT NULL,
  `aangemaakt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rekeningnummer` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rijksregisternummer` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tshirtmaat` enum('XS','S','M','L','XL','XXL') COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`lid_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leden_groepen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leden_groepen` (
  `lid_id` int NOT NULL,
  `groep_id` int NOT NULL,
  PRIMARY KEY (`lid_id`,`groep_id`),
  UNIQUE KEY `uq_leden_groepen_lid` (`lid_id`),
  KEY `groep_id` (`groep_id`),
  CONSTRAINT `leden_groepen_ibfk_1` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE CASCADE,
  CONSTRAINT `leden_groepen_ibfk_2` FOREIGN KEY (`groep_id`) REFERENCES `groepen` (`groep_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leden_identificatie_legacy_backup_20260812`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leden_identificatie_legacy_backup_20260812` (
  `lid_id` int NOT NULL,
  `rijksregisternummer_legacy` varchar(512) COLLATE utf8mb4_general_ci NOT NULL,
  `bijgewerkt_op_legacy` timestamp NULL DEFAULT NULL,
  `gebackupt_op` datetime NOT NULL,
  PRIMARY KEY (`lid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lidmaatschappen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lidmaatschappen` (
  `lidmaatschap_id` int NOT NULL AUTO_INCREMENT,
  `lid_id` int NOT NULL,
  `lidtype_id` int NOT NULL,
  `jaar` int NOT NULL,
  `aangemaakt_op` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lidmaatschap_id`),
  UNIQUE KEY `lid_id` (`lid_id`,`jaar`),
  KEY `fk_lidmaatschap_type` (`lidtype_id`),
  CONSTRAINT `fk_lidmaatschap_lid` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lidmaatschap_type` FOREIGN KEY (`lidtype_id`) REFERENCES `lidtypes` (`lidtype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lidtypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lidtypes` (
  `lidtype_id` int NOT NULL AUTO_INCREMENT,
  `naam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `bedrag` decimal(10,2) NOT NULL,
  PRIMARY KEY (`lidtype_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailing_bijlagen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailing_bijlagen` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailing_ontvangers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailing_ontvangers` (
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
) ENGINE=InnoDB AUTO_INCREMENT=161 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailings` (
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meldingen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meldingen` (
  `melding_id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `titel` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `bericht` text COLLATE utf8mb4_general_ci NOT NULL,
  `gelezen` tinyint(1) DEFAULT '0',
  `aangemaakt_op` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`melding_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_inschrijvingen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_inschrijvingen` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2378 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_inschrijvingen_legacy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_inschrijvingen_legacy` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shift_id` int NOT NULL,
  `lid_id` int NOT NULL,
  `aangemaakt_op` datetime DEFAULT CURRENT_TIMESTAMP,
  `aanwezig` tinyint(1) NOT NULL DEFAULT '0',
  `aanwezig_afgevinkt_op` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_id` (`shift_id`,`lid_id`),
  KEY `lid_id` (`lid_id`),
  CONSTRAINT `shift_inschrijvingen_legacy_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `event_shifts_legacy` (`shift_id`) ON DELETE CASCADE,
  CONSTRAINT `shift_inschrijvingen_legacy_ibfk_2` FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shift_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_types` (
  `type_id` int unsigned NOT NULL AUTO_INCREMENT,
  `naam` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `kleur` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '#1E3A8A',
  `icoon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `omschrijving` text COLLATE utf8mb4_general_ci,
  `actief` tinyint(1) NOT NULL DEFAULT '1',
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `uq_shift_types_naam` (`naam`),
  KEY `idx_shift_types_actief` (`actief`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
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
  `vergoeding_bedrag` decimal(10,2) NOT NULL DEFAULT '30.00',
  PRIMARY KEY (`shift_id`),
  KEY `idx_shifts_event` (`event_id`),
  KEY `idx_shifts_type` (`type_id`),
  KEY `idx_shifts_start` (`start_op`),
  KEY `idx_shifts_status` (`status`),
  KEY `idx_shifts_event_start` (`event_id`,`start_op`),
  CONSTRAINT `fk_shifts_event` FOREIGN KEY (`event_id`) REFERENCES `evenementen` (`event_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_shifts_type` FOREIGN KEY (`type_id`) REFERENCES `shift_types` (`type_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_shifts_capacity` CHECK ((`max_personen` > 0)),
  CONSTRAINT `chk_shifts_period` CHECK ((`eind_op` > `start_op`)),
  CONSTRAINT `chk_shifts_vergoeding_bedrag` CHECK ((`vergoeding_bedrag` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
