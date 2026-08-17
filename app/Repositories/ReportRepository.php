<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use PDO;

final class ReportRepository
{
    public function __construct(
        private readonly Database $database
    ) {
    }

    /**
     * @return array<int, array{
     *     inschrijving_id: int,
     *     shift_id: int,
     *     lid_id: int,
     *     voornaam: string,
     *     achternaam: string,
     *     rekeningnummer: ?string,
     *     werkdatum: string,
     *     vergoeding_bedrag: string,
     *     groep_id: ?int,
     *     groep_naam: ?string
     * }>
     */
    public function workedShiftCompensations(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }

        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                si.inschrijving_id,
                s.shift_id,
                si.lid_id,
                l.voornaam,
                l.achternaam,
                l.rekeningnummer,
                DATE(s.start_op) AS werkdatum,
                s.vergoeding_bedrag,
                g.groep_id,
                g.naam AS groep_naam
            FROM shifts AS s
            INNER JOIN shift_inschrijvingen AS si
                ON si.shift_id = s.shift_id
               AND si.status = 'bevestigd'
               AND si.aanwezig = 1
            INNER JOIN leden AS l
                ON l.lid_id = si.lid_id
            LEFT JOIN leden_groepen AS lg
                ON lg.lid_id = l.lid_id
            LEFT JOIN groepen AS g
                ON g.groep_id = lg.groep_id
            WHERE s.event_id = :event_id
            ORDER BY
                g.naam ASC,
                l.achternaam ASC,
                l.voornaam ASC,
                s.start_op ASC,
                s.shift_id ASC
            SQL);

        $statement->execute([
            'event_id' => $eventId,
        ]);

        return array_map(
            static fn(array $row): array => [
                'inschrijving_id' => (int) $row['inschrijving_id'],
                'shift_id' => (int) $row['shift_id'],
                'lid_id' => (int) $row['lid_id'],
                'voornaam' => (string) $row['voornaam'],
                'achternaam' => (string) $row['achternaam'],
                'rekeningnummer' => $row['rekeningnummer'] !== null
                    ? (string) $row['rekeningnummer']
                    : null,
                'werkdatum' => (string) $row['werkdatum'],
                'vergoeding_bedrag' => (string) $row['vergoeding_bedrag'],
                'groep_id' => $row['groep_id'] !== null
                    ? (int) $row['groep_id']
                    : null,
                'groep_naam' => $row['groep_naam'] !== null
                    ? (string) $row['groep_naam']
                    : null,
            ],
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}
