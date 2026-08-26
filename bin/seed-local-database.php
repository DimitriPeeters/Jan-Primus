<?php

declare(strict_types=1);

use AEFS\Core\Config;
use AEFS\Core\Database;
use App\Services\EncryptionService;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$config = new Config($root . '/config');
$databaseName = (string) $config->get('database.connections.mysql.database', '');

if ($databaseName !== 'jan_primus_local') {
    fwrite(STDERR, "Seed afgebroken: alleen jan_primus_local is toegestaan.\n");
    exit(1);
}

$database = new Database($config);
$encryption = new EncryptionService($config);
$passwordHash = password_hash('JanPrimus!2026', PASSWORD_DEFAULT);

$members = [
    ['Dimitri', 'Peeters', 'admin@jan-primus.test', 'admin', 'goedgekeurd', 1, 'M', 'L'],
    ['Anke', 'Janssens', 'anke.janssens@jan-primus.test', 'lid', 'goedgekeurd', 1, 'V', 'M'],
    ['Bram', 'Vermeulen', 'bram.vermeulen@jan-primus.test', 'lid', 'goedgekeurd', 1, 'M', 'XL'],
    ['Charlotte', 'Willems', 'charlotte.willems@jan-primus.test', 'lid', 'wachtend', 0, 'V', 'S'],
    ['Noa', 'Dubois', 'noa.dubois@jan-primus.test', 'lid', 'goedgekeurd', 1, 'X', 'M'],
    ['Pieter', 'Maes', 'pieter.maes@jan-primus.test', 'lid', 'goedgekeurd', 0, 'M', 'XXL'],
];

$database->transaction(function () use (
    $database,
    $encryption,
    $passwordHash,
    $members
): void {
    foreach ($members as $index => $member) {
        [$firstName, $lastName, $email, $role, $approval, $active, $gender, $size] = $member;

        $existing = $database->prepare('SELECT gebruiker_id FROM gebruikers WHERE email = :email');
        $existing->execute(['email' => $email]);
        if ($existing->fetchColumn() !== false) {
            continue;
        }

        $number = sprintf('TEST-%02d-000000', $index + 1);
        $insertMember = $database->prepare(<<<'SQL'
            INSERT INTO leden (
                voornaam, achternaam, actief, straat, postcode, gemeente, land,
                telefoon, geboortedatum, geslacht, opmerkingen, gdpr_consent,
                gdpr_timestamp, toegetreden_op, uitgetreden_op,
                rijksregisternummer, tshirtmaat
            ) VALUES (
                :voornaam, :achternaam, :actief, :straat, :postcode, :gemeente,
                'België', :telefoon, :geboortedatum, :geslacht, :opmerkingen, 1,
                NOW(), :toegetreden_op, :uitgetreden_op,
                :rijksregisternummer, :tshirtmaat
            )
            SQL);
        $insertMember->execute([
            'voornaam' => $firstName,
            'achternaam' => $lastName,
            'actief' => $active,
            'straat' => 'Teststraat ' . ($index + 1),
            'postcode' => sprintf('35%02d', $index + 10),
            'gemeente' => $index % 2 === 0 ? 'Hasselt' : 'Diepenbeek',
            'telefoon' => '+32 470 00 00 ' . sprintf('%02d', $index + 1),
            'geboortedatum' => sprintf('%d-%02d-15', 1980 + $index * 4, $index + 1),
            'geslacht' => $gender,
            'opmerkingen' => 'Fictief testprofiel voor lokale ontwikkeling.',
            'toegetreden_op' => $approval === 'goedgekeurd' ? '2025-01-15' : null,
            'uitgetreden_op' => $approval === 'goedgekeurd' && !$active ? '2026-06-30' : null,
            'rijksregisternummer' => $encryption->encrypt($number),
            'tshirtmaat' => $size,
        ]);

        $memberId = $database->lastInsertId();
        $insertUser = $database->prepare(<<<'SQL'
            INSERT INTO gebruikers (
                lid_id, email, wachtwoord_hash, rol, goedkeuringsstatus,
                goedgekeurd_op, actief, wachtwoord_moet_wijzigen, mail_blacklist
            ) VALUES (
                :lid_id, :email, :wachtwoord_hash, :rol, :goedkeuringsstatus,
                :goedgekeurd_op, :actief, 0, 0
            )
            SQL);
        $insertUser->execute([
            'lid_id' => $memberId,
            'email' => $email,
            'wachtwoord_hash' => $passwordHash,
            'rol' => $role,
            'goedkeuringsstatus' => $approval,
            'goedgekeurd_op' => $approval === 'goedgekeurd' ? '2025-01-15 12:00:00' : null,
            'actief' => $active,
        ]);
    }

    $database->query(<<<'SQL'
        INSERT IGNORE INTO shift_types (type_id, naam, kleur, icoon, omschrijving, actief)
        VALUES
            (1, 'Opbouw', '#EF6012', 'tools', 'Voorbereiding en inrichting', 1),
            (2, 'Toog', '#2F315D', 'glass', 'Drankenbediening', 1),
            (3, 'Afbraak', '#6B4A2D', 'box', 'Opruimen na het evenement', 1)
        SQL);

    $eventCount = (int) $database->query('SELECT COUNT(*) FROM evenementen')->fetchColumn();
    if ($eventCount === 0) {
        $database->query(<<<'SQL'
            INSERT INTO evenementen
                (titel, beschrijving, locatie, max_deelnemers, startdatum, einddatum, status)
            VALUES
                ('Primusfeesten 2026', 'Lokaal testevenement voor de ledenplanning.', 'Hasselt', 80, '2026-09-05', '2026-09-06', 'gepubliceerd'),
                ('Vrijwilligersavond', 'Kennismaking en praktische briefing.', 'Diepenbeek', 40, '2026-10-16', NULL, 'concept')
            SQL);
        $eventId = $database->lastInsertId();
        $database->prepare(<<<'SQL'
            INSERT INTO shifts (event_id, type_id, naam, start_op, eind_op, max_personen, status)
            VALUES
                (:event_id_1, 1, 'Zaterdag opbouw', '2026-09-05 08:00:00', '2026-09-05 12:00:00', 12, 'actief'),
                (:event_id_2, 2, 'Avondshift toog', '2026-09-05 18:00:00', '2026-09-06 01:00:00', 10, 'actief'),
                (:event_id_3, 3, 'Zondag afbraak', '2026-09-06 18:00:00', '2026-09-06 22:00:00', 15, 'actief')
            SQL)->execute([
                'event_id_1' => $eventId,
                'event_id_2' => $eventId,
                'event_id_3' => $eventId,
            ]);
    }

    $database->query(<<<'SQL'
        INSERT INTO event_inschrijvingen
            (event_id, lid_id, status, aangemeld_op)
        SELECT e.event_id, l.lid_id, 'bevestigd', NOW()
        FROM evenementen e
        INNER JOIN leden l ON l.voornaam = 'Anke' AND l.achternaam = 'Janssens'
        WHERE e.titel = 'Primusfeesten 2026'
        ON DUPLICATE KEY UPDATE
            status = 'bevestigd',
            annulatie_aangevraagd_op = NULL,
            uitgeschreven_op = NULL
        SQL);

    $database->query(<<<'SQL'
        INSERT IGNORE INTO event_inschrijving_dagen (inschrijving_id, datum)
        SELECT ei.inschrijving_id, '2026-09-05'
        FROM event_inschrijvingen ei
        INNER JOIN evenementen e ON e.event_id = ei.event_id
        INNER JOIN leden l ON l.lid_id = ei.lid_id
        WHERE e.titel = 'Primusfeesten 2026'
          AND l.voornaam = 'Anke'
          AND l.achternaam = 'Janssens'
          AND NOT EXISTS (
              SELECT 1 FROM event_inschrijving_dagen existing_day
              WHERE existing_day.inschrijving_id = ei.inschrijving_id
                AND existing_day.datum = '2026-09-05'
          )
        SQL);

    $database->query(<<<'SQL'
        INSERT INTO event_inschrijvingen
            (event_id, lid_id, status, aangemeld_op)
        SELECT e.event_id, l.lid_id, 'wachtend', NOW()
        FROM evenementen e
        INNER JOIN leden l ON l.voornaam = 'Bram' AND l.achternaam = 'Vermeulen'
        WHERE e.titel = 'Primusfeesten 2026'
        ON DUPLICATE KEY UPDATE
            status = 'wachtend',
            annulatie_aangevraagd_op = NULL,
            uitgeschreven_op = NULL
        SQL);

    $database->query(<<<'SQL'
        INSERT IGNORE INTO event_inschrijving_dagen (inschrijving_id, datum)
        SELECT ei.inschrijving_id, '2026-09-06'
        FROM event_inschrijvingen ei
        INNER JOIN evenementen e ON e.event_id = ei.event_id
        INNER JOIN leden l ON l.lid_id = ei.lid_id
        WHERE e.titel = 'Primusfeesten 2026'
          AND l.voornaam = 'Bram'
          AND l.achternaam = 'Vermeulen'
          AND NOT EXISTS (
              SELECT 1 FROM event_inschrijving_dagen existing_day
              WHERE existing_day.inschrijving_id = ei.inschrijving_id
                AND existing_day.datum = '2026-09-06'
          )
        SQL);
});

echo "Lokale testdata is beschikbaar.\n";
echo "Beheerder: admin@jan-primus.test\n";
echo "Wachtwoord: JanPrimus!2026\n";
