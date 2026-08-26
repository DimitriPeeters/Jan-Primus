<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use App\Mappers\EventRegistrationMapper;
use App\Models\EventRegistration;
use PDO;

final class EventRegistrationRepository
{
    private const SELECT_REGISTRATION = <<<'SQL'
        SELECT
            ei.*,
            l.voornaam AS lid_voornaam,
            l.achternaam AS lid_achternaam,
            u.email AS lid_email,
            e.titel AS event_titel,
            e.startdatum AS event_startdatum,
            e.einddatum AS event_einddatum,
            (
                SELECT GROUP_CONCAT(
                    DATE_FORMAT(eid.datum, '%Y-%m-%d')
                    ORDER BY eid.datum
                    SEPARATOR ','
                )
                FROM event_inschrijving_dagen eid
                WHERE eid.inschrijving_id = ei.inschrijving_id
            ) AS dagen_csv
        FROM event_inschrijvingen ei
        INNER JOIN leden l
            ON l.lid_id = ei.lid_id
        INNER JOIN gebruikers u
            ON u.lid_id = l.lid_id
        INNER JOIN evenementen e
            ON e.event_id = ei.event_id
        SQL;

    public function __construct(
        private readonly Database $database,
        private readonly EventRegistrationMapper $mapper
    ) {
    }

    public function find(int $id): ?EventRegistration
    {
        return $this->findUsingCondition(
            'ei.inschrijving_id = :inschrijving_id',
            [
                'inschrijving_id' => $id,
            ]
        );
    }

    public function findForUpdate(int $id): ?EventRegistration
    {
        return $this->findUsingCondition(
            'ei.inschrijving_id = :inschrijving_id',
            [
                'inschrijving_id' => $id,
            ],
            true
        );
    }

    public function findByEventAndMember(
        int $eventId,
        int $memberId
    ): ?EventRegistration {
        return $this->findUsingCondition(
            <<<'SQL'
                ei.event_id = :event_id
                AND ei.lid_id = :lid_id
                SQL,
            [
                'event_id' => $eventId,
                'lid_id' => $memberId,
            ]
        );
    }

    /**
     * @return EventRegistration[]
     */
    public function findByEvent(int $eventId): array
    {
        $statement = $this->database->prepare(
            self::SELECT_REGISTRATION
            . PHP_EOL
            . <<<'SQL'
                WHERE ei.event_id = :event_id
                ORDER BY
                    (
                        ei.annulatie_aangevraagd_op IS NOT NULL
                        AND ei.uitgeschreven_op IS NULL
                    ) DESC,
                    FIELD(
                        ei.status,
                        'wachtend',
                        'bevestigd',
                        'reserve',
                        'geweigerd'
                    ),
                    l.voornaam ASC,
                    l.achternaam ASC,
                    ei.inschrijving_id ASC
                SQL
        );

        $statement->execute([
            'event_id' => $eventId,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return EventRegistration[]
     */
    public function findEligibleForShift(
        int $eventId,
        int $shiftId,
        string $shiftDate
    ): array {
        $statement = $this->database->prepare(
            self::SELECT_REGISTRATION
            . PHP_EOL
            . <<<'SQL'
                WHERE ei.event_id = :event_id
                  AND ei.status <> :rejected_status
                  AND ei.uitgeschreven_op IS NULL
                  AND ei.annulatie_aangevraagd_op IS NULL
                  AND (
                      EXISTS (
                          SELECT 1
                          FROM event_inschrijving_dagen selected_day
                          WHERE selected_day.inschrijving_id = ei.inschrijving_id
                            AND selected_day.datum = :selected_shift_date
                      )
                      OR (
                          NOT EXISTS (
                              SELECT 1
                              FROM event_inschrijving_dagen any_day
                              WHERE any_day.inschrijving_id = ei.inschrijving_id
                          )
                          AND e.startdatum = :legacy_shift_date
                      )
                  )
                  AND NOT EXISTS (
                      SELECT 1
                      FROM shift_inschrijvingen si
                      WHERE si.shift_id = :shift_id
                        AND si.lid_id = ei.lid_id
                        AND si.status IN ('wachtend', 'bevestigd', 'reserve')
                  )
                ORDER BY l.voornaam ASC, l.achternaam ASC
                SQL
        );

        $statement->execute([
            'event_id' => $eventId,
            'rejected_status' => EventRegistration::STATUS_GEWEIGERD,
            'selected_shift_date' => $shiftDate,
            'legacy_shift_date' => $shiftDate,
            'shift_id' => $shiftId,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function submit(
        int $eventId,
        int $memberId
    ): int {
        $statement = $this->database->prepare(<<<'SQL'
            INSERT INTO event_inschrijvingen
            (
                event_id,
                lid_id,
                status,
                aangemeld_op,
                uitschrijfreden,
                annulatie_aangevraagd_op,
                uitgeschreven_op,
                annulatie_bevestigd_door
            )
            VALUES
            (
                :event_id,
                :lid_id,
                :status,
                NOW(),
                NULL,
                NULL,
                NULL,
                NULL
            )
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                aangemeld_op = NOW(),
                uitschrijfreden = NULL,
                annulatie_aangevraagd_op = NULL,
                uitgeschreven_op = NULL,
                annulatie_bevestigd_door = NULL,
                inschrijving_id = LAST_INSERT_ID(inschrijving_id)
            SQL);

        $statement->execute([
            'event_id' => $eventId,
            'lid_id' => $memberId,
            'status' => EventRegistration::STATUS_WACHTEND,
        ]);

        return $this->database->lastInsertId();
    }

    /**
     * @param string[] $days
     */
    public function replaceDays(
        int $registrationId,
        array $days
    ): void {
        $delete = $this->database->prepare(<<<'SQL'
            DELETE FROM event_inschrijving_dagen
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $delete->execute([
            'inschrijving_id' => $registrationId,
        ]);

        $insert = $this->database->prepare(<<<'SQL'
            INSERT INTO event_inschrijving_dagen
            (
                inschrijving_id,
                datum
            )
            VALUES
            (
                :inschrijving_id,
                :datum
            )
            SQL);

        foreach ($days as $day) {
            $insert->execute([
                'inschrijving_id' => $registrationId,
                'datum' => $day,
            ]);
        }
    }

    public function setStatus(int $id, string $status): void
    {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE event_inschrijvingen
            SET
                status = :status,
                uitschrijfreden = NULL,
                annulatie_aangevraagd_op = NULL,
                uitgeschreven_op = NULL,
                annulatie_bevestigd_door = NULL
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
            'status' => $status,
        ]);
    }

    public function requestCancellation(
        int $id,
        ?string $reason
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE event_inschrijvingen
            SET
                uitschrijfreden = :uitschrijfreden,
                annulatie_aangevraagd_op = NOW(),
                uitgeschreven_op = NULL,
                annulatie_bevestigd_door = NULL
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
            'uitschrijfreden' => $reason,
        ]);
    }

    public function cancelWithoutVerification(
        int $id,
        ?string $reason
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE event_inschrijvingen
            SET
                uitschrijfreden = :uitschrijfreden,
                annulatie_aangevraagd_op = NOW(),
                uitgeschreven_op = NOW(),
                annulatie_bevestigd_door = NULL
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
            'uitschrijfreden' => $reason,
        ]);
    }

    public function confirmCancellation(
        int $id,
        int $confirmedBy
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE event_inschrijvingen
            SET
                uitgeschreven_op = NOW(),
                annulatie_bevestigd_door = :annulatie_bevestigd_door
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
            'annulatie_bevestigd_door' => $confirmedBy,
        ]);
    }

    public function cancelForEventCancellation(
        int $id,
        int $cancelledBy,
        string $reason
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE event_inschrijvingen
            SET
                uitschrijfreden = :uitschrijfreden,
                annulatie_aangevraagd_op = NULL,
                uitgeschreven_op = NOW(),
                annulatie_bevestigd_door = :annulatie_bevestigd_door
            WHERE inschrijving_id = :inschrijving_id
              AND status IN ('wachtend', 'bevestigd', 'reserve')
              AND uitgeschreven_op IS NULL
            SQL);
        $statement->execute([
            'inschrijving_id' => $id,
            'uitschrijfreden' => $reason,
            'annulatie_bevestigd_door' => $cancelledBy,
        ]);
    }

    public function countConfirmed(int $eventId): int
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM event_inschrijvingen
            WHERE event_id = :event_id
              AND status = :status
              AND uitgeschreven_op IS NULL
            SQL);

        $statement->execute([
            'event_id' => $eventId,
            'status' => EventRegistration::STATUS_BEVESTIGD,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function countActiveShiftAssignments(
        int $eventId,
        int $memberId
    ): int {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM shift_inschrijvingen si
            INNER JOIN shifts s
                ON s.shift_id = si.shift_id
            WHERE s.event_id = :event_id
              AND si.lid_id = :lid_id
              AND si.status IN ('wachtend', 'bevestigd', 'reserve')
            SQL);

        $statement->execute([
            'event_id' => $eventId,
            'lid_id' => $memberId,
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function findUsingCondition(
        string $condition,
        array $parameters,
        bool $forUpdate = false
    ): ?EventRegistration {
        $sql = self::SELECT_REGISTRATION
            . PHP_EOL
            . 'WHERE '
            . $condition
            . PHP_EOL
            . 'LIMIT 1';

        if ($forUpdate) {
            $sql .= PHP_EOL . 'FOR UPDATE';
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->fromDatabase($row)
            : null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return EventRegistration[]
     */
    private function mapRows(array $rows): array
    {
        return array_map(
            fn(array $row): EventRegistration => $this->mapper
                ->fromDatabase($row),
            $rows
        );
    }
}
