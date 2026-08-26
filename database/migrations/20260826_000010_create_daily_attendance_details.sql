-- Jan Primus Ledenbeheer
-- Additieve migratie: daggegevens voor de aanwezigheidslijst.

CREATE TABLE IF NOT EXISTS `dag_aanwezigheden` (
  `dag_aanwezigheid_id` int unsigned NOT NULL AUTO_INCREMENT,
  `datum` date NOT NULL,
  `lid_id` int NOT NULL,
  `nummer_walkie` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `oortje` tinyint(1) NOT NULL DEFAULT '0',
  `aangemaakt_op` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bijgewerkt_op` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`dag_aanwezigheid_id`),
  UNIQUE KEY `uq_dag_aanwezigheden_datum_lid` (`datum`,`lid_id`),
  KEY `idx_dag_aanwezigheden_lid` (`lid_id`),
  CONSTRAINT `fk_dag_aanwezigheden_lid`
    FOREIGN KEY (`lid_id`) REFERENCES `leden` (`lid_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_dag_aanwezigheden_walkie`
    CHECK (`nummer_walkie` IS NULL OR char_length(`nummer_walkie`) <= 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
