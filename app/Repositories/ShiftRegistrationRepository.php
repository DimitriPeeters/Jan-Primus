<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use App\Mappers\ShiftRegistrationMapper;
use App\Models\ShiftRegistration;
use PDO;

final class ShiftRegistrationRepository
{
    private const SELECT_REGISTRATION = <<<'SQL'
        SELECT
            si.*,
            l.voornaam AS lid_voornaam,
            l.achternaam AS lid_achternaam,
            u.email AS lid_email,
            s.naam AS shift_naam,
            s.start_op AS shift_start_op,
            s.eind_op AS shift_eind_op,
            e.titel AS event_titel,
            st.naam AS type_naam,
            NULLIF(
                TRIM(CONCAT_WS(' ', gl.voornaam, gl.achternaam)),
                ''
            ) AS goedgekeurd_door_naam,
            NULLIF(
                TRIM(CONCAT_WS(' ', al.voornaam, al.achternaam)),
                ''
            ) AS geannuleerd_door_naam
        FROM shift_inschrijvingen si
        INNER JOIN leden l
            ON l.lid_id = si.lid_id
        INNER JOIN gebruikers u
            ON u.lid_id = l.lid_id
        INNER JOIN shifts s
            ON s.shift_id = si.shift_id
        INNER JOIN evenementen e
            ON e.event_id = s.event_id
        INNER JOIN shift_types st
            ON st.type_id = s.type_id
        LEFT JOIN gebruikers gu
            ON gu.gebruiker_id = si.goedgekeurd_door
        LEFT JOIN leden gl
            ON gl.lid_id = gu.lid_id
        LEFT JOIN gebruikers au
            ON au.gebruiker_id = si.geannuleerd_door
        LEFT JOIN leden al
            ON al.lid_id = au.lid_id
        SQL;

    public function __construct(
        private readonly Database $database,
        private readonly ShiftRegistrationMapper $mapper
    ) {
    }

    public function find(int $id): ?ShiftRegistration
    {
        return $this->findUsingCondition(
            'si.inschrijving_id = :inschrijving_id',
            [
                'inschrijving_id' => $id,
            ]
        );
    }

    public function findForUpdate(int $id): ?ShiftRegistration
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                si.*,
                l.voornaam AS lid_voornaam,
                l.achternaam AS lid_achternaam,
                u.email AS lid_email,
                s.naam AS shift_naam,
                s.start_op AS shift_start_op,
                s.eind_op AS shift_eind_op,
                e.titel AS event_titel,
                st.naam AS type_naam,
                NULL AS goedgekeurd_door_naam,
                NULL AS geannuleerd_door_naam
            FROM shift_inschrijvingen si
            INNER JOIN leden l
                ON l.lid_id = si.lid_id
            INNER JOIN gebruikers u
                ON u.lid_id = l.lid_id
            INNER JOIN shifts s
                ON s.shift_id = si.shift_id
            INNER JOIN evenementen e
                ON e.event_id = s.event_id
            INNER JOIN shift_types st
                ON st.type_id = s.type_id
            WHERE si.inschrijving_id = :inschrijving_id
            LIMIT 1
            FOR UPDATE
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->fromDatabase($row)
            : null;
    }

    public function findByShiftAndMember(
        int $shiftId,
        int $memberId
    ): ?ShiftRegistration {
        return $this->findUsingCondition(
            <<<'SQL'
                si.shift_id = :shift_id
                AND si.lid_id = :lid_id
                SQL,
            [
                'shift_id' => $shiftId,
                'lid_id' => $memberId,
            ]
        );
    }

    /**
     * @return ShiftRegistration[]
     */
    public function findByShift(int $shiftId): array
    {
        $statement = $this->database->prepare(
            self::SELECT_REGISTRATION
            . PHP_EOL
            . <<<'SQL'
                WHERE si.shift_id = :shift_id
                ORDER BY
                    FIELD(
                        si.status,
                        'wachtend',
                        'bevestigd',
                        'reserve',
                        'geweigerd',
                        'geannuleerd'
                    ),
                    si.aangemaakt_op ASC,
                    si.inschrijving_id ASC
                SQL
        );

        $statement->execute([
            'shift_id' => $shiftId,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return ShiftRegistration[]
     */
    public function findByMember(int $memberId): array
    {
        $statement = $this->database->prepare(
            self::SELECT_REGISTRATION
            . PHP_EOL
            . <<<'SQL'
                WHERE si.lid_id = :lid_id
                ORDER BY
                    s.start_op DESC,
                    si.inschrijving_id DESC
                SQL
        );

        $statement->execute([
            'lid_id' => $memberId,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return ShiftRegistration[]
     */
    public function findPending(): array
    {
        $statement = $this->database->prepare(
            self::SELECT_REGISTRATION
            . PHP_EOL
            . <<<'SQL'
                WHERE si.status = :status
                ORDER BY s.start_op ASC, si.aangemaakt_op ASC
                SQL
        );

        $statement->execute([
            'status' => ShiftRegistration::STATUS_WACHTEND,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return ShiftRegistration[]
     */
    public function findActiveByEventAndMember(
        int $eventId,
        int $memberId
    ): array {
        $statement = $this->database->prepare(
            self::SELECT_REGISTRATION
            . PHP_EOL
            . <<<'SQL'
                WHERE s.event_id = :event_id
                  AND si.lid_id = :lid_id
                  AND si.status IN (
                      :waiting_status,
                      :confirmed_status,
                      :reserve_status
                  )
                ORDER BY s.start_op ASC, si.inschrijving_id ASC
                SQL
        );

        $statement->execute([
            'event_id' => $eventId,
            'lid_id' => $memberId,
            'waiting_status' => ShiftRegistration::STATUS_WACHTEND,
            'confirmed_status' => ShiftRegistration::STATUS_BEVESTIGD,
            'reserve_status' => ShiftRegistration::STATUS_RESERVE,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function assign(
        int $shiftId,
        int $memberId,
        string $status,
        int $approvedBy
    ): int {
        $statement = $this->database->prepare(<<<'SQL'
            INSERT INTO shift_inschrijvingen
            (
                shift_id,
                lid_id,
                status,
                opmerking_lid,
                goedgekeurd_door,
                goedgekeurd_op,
                geannuleerd_door,
                geannuleerd_op,
                annulatie_reden,
                aanwezig,
                aanwezig_afgevinkt_op,
                aangemaakt_op,
                bijgewerkt_op
            )
            VALUES
            (
                :shift_id,
                :lid_id,
                :status,
                NULL,
                :goedgekeurd_door,
                NOW(),
                NULL,
                NULL,
                NULL,
                0,
                NULL,
                NOW(),
                NULL
            )
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                opmerking_lid = NULL,
                goedgekeurd_door = VALUES(goedgekeurd_door),
                goedgekeurd_op = NOW(),
                geannuleerd_door = NULL,
                geannuleerd_op = NULL,
                annulatie_reden = NULL,
                aanwezig = 0,
                aanwezig_afgevinkt_op = NULL,
                aangemaakt_op = NOW(),
                bijgewerkt_op = NOW(),
                inschrijving_id = LAST_INSERT_ID(inschrijving_id)
            SQL);

        $statement->execute([
            'shift_id' => $shiftId,
            'lid_id' => $memberId,
            'status' => $status,
            'goedgekeurd_door' => $approvedBy,
        ]);

        return $this->database->lastInsertId();
    }

    public function setDecision(
        int $id,
        string $status,
        int $approvedBy
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shift_inschrijvingen
            SET
                status = :status,
                goedgekeurd_door = :goedgekeurd_door,
                goedgekeurd_op = NOW(),
                geannuleerd_door = NULL,
                geannuleerd_op = NULL,
                annulatie_reden = NULL,
                bijgewerkt_op = NOW()
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
            'status' => $status,
            'goedgekeurd_door' => $approvedBy,
        ]);
    }

    public function cancel(
        int $id,
        int $cancelledBy,
        ?string $reason
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shift_inschrijvingen
            SET
                status = :status,
                geannuleerd_door = :geannuleerd_door,
                geannuleerd_op = NOW(),
                annulatie_reden = :annulatie_reden,
                aanwezig = 0,
                aanwezig_afgevinkt_op = NULL,
                bijgewerkt_op = NOW()
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
            'status' => ShiftRegistration::STATUS_GEANNULEERD,
            'geannuleerd_door' => $cancelledBy,
            'annulatie_reden' => $reason,
        ]);
    }

    public function cancelActiveByShift(
        int $shiftId,
        int $cancelledBy,
        ?string $reason
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shift_inschrijvingen
            SET
                status = :cancelled_status,
                geannuleerd_door = :geannuleerd_door,
                geannuleerd_op = NOW(),
                annulatie_reden = :annulatie_reden,
                aanwezig = 0,
                aanwezig_afgevinkt_op = NULL,
                bijgewerkt_op = NOW()
            WHERE shift_id = :shift_id
              AND status IN (
                  :waiting_status,
                  :confirmed_status,
                  :reserve_status
              )
            SQL);

        $statement->execute([
            'shift_id' => $shiftId,
            'cancelled_status' => ShiftRegistration::STATUS_GEANNULEERD,
            'waiting_status' => ShiftRegistration::STATUS_WACHTEND,
            'confirmed_status' => ShiftRegistration::STATUS_BEVESTIGD,
            'reserve_status' => ShiftRegistration::STATUS_RESERVE,
            'geannuleerd_door' => $cancelledBy,
            'annulatie_reden' => $reason,
        ]);
    }

    public function setPresence(
        int $id,
        bool $present
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shift_inschrijvingen
            SET
                aanwezig = :aanwezig,
                aanwezig_afgevinkt_op = CASE
                    WHEN :aanwezig_timestamp = 1 THEN NOW()
                    ELSE NULL
                END,
                bijgewerkt_op = NOW()
            WHERE inschrijving_id = :inschrijving_id
            SQL);

        $statement->execute([
            'inschrijving_id' => $id,
            'aanwezig' => $present ? 1 : 0,
            'aanwezig_timestamp' => $present ? 1 : 0,
        ]);
    }

    public function countByStatus(
        int $shiftId,
        string $status
    ): int {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM shift_inschrijvingen
            WHERE shift_id = :shift_id
              AND status = :status
            SQL);

        $statement->execute([
            'shift_id' => $shiftId,
            'status' => $status,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function findNextReserve(
        int $shiftId
    ): ?ShiftRegistration {
        return $this->findUsingCondition(
            <<<'SQL'
                si.shift_id = :shift_id
                AND si.status = :status
                SQL,
            [
                'shift_id' => $shiftId,
                'status' => ShiftRegistration::STATUS_RESERVE,
            ],
            'si.aangemaakt_op ASC, si.inschrijving_id ASC'
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function findUsingCondition(
        string $condition,
        array $parameters,
        ?string $orderBy = null
    ): ?ShiftRegistration {
        $sql = self::SELECT_REGISTRATION
            . PHP_EOL
            . 'WHERE '
            . $condition;

        if ($orderBy !== null) {
            $sql .= PHP_EOL . 'ORDER BY ' . $orderBy;
        }

        $sql .= PHP_EOL . 'LIMIT 1';

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
     * @return ShiftRegistration[]
     */
    private function mapRows(array $rows): array
    {
        return array_map(
            fn(array $row): ShiftRegistration => $this->mapper->fromDatabase($row),
            $rows
        );
    }
}
