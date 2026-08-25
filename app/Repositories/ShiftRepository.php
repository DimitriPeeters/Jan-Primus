<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use App\Mappers\ShiftMapper;
use App\Models\Event;
use App\Models\Shift;
use PDO;

final class ShiftRepository
{
    private const SELECT_SHIFT = <<<'SQL'
        SELECT
            s.*,
            e.titel AS event_titel,
            e.startdatum AS event_startdatum,
            e.einddatum AS event_einddatum,
            e.status AS event_status,
            st.naam AS type_naam,
            st.kleur AS type_kleur,
            st.icoon AS type_icoon,
            st.omschrijving AS type_omschrijving,
            (
                SELECT COUNT(*)
                FROM shift_inschrijvingen si
                WHERE si.shift_id = s.shift_id
                  AND si.status = 'wachtend'
            ) AS aantal_wachtend,
            (
                SELECT COUNT(*)
                FROM shift_inschrijvingen si
                WHERE si.shift_id = s.shift_id
                  AND si.status = 'bevestigd'
            ) AS aantal_bevestigd,
            (
                SELECT COUNT(*)
                FROM shift_inschrijvingen si
                WHERE si.shift_id = s.shift_id
                  AND si.status = 'reserve'
            ) AS aantal_reserve,
            (
                SELECT COUNT(*)
                FROM shift_inschrijvingen si
                WHERE si.shift_id = s.shift_id
                  AND si.status = 'geweigerd'
            ) AS aantal_geweigerd,
            (
                SELECT COUNT(*)
                FROM shift_inschrijvingen si
                WHERE si.shift_id = s.shift_id
                  AND si.status = 'geannuleerd'
            ) AS aantal_geannuleerd
        FROM shifts s
        INNER JOIN evenementen e
            ON e.event_id = s.event_id
        INNER JOIN shift_types st
            ON st.type_id = s.type_id
        SQL;

    public function __construct(
        private readonly Database $database,
        private readonly ShiftMapper $mapper
    ) {
    }

    /**
     * @return Shift[]
     */
    public function allForAdministration(): array
    {
        $statement = $this->database->query(
            self::SELECT_SHIFT
            . PHP_EOL
            . 'ORDER BY s.start_op ASC, s.shift_id ASC'
        );

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return Shift[]
     */
    public function visibleToMembers(): array
    {
        $statement = $this->database->prepare(
            self::SELECT_SHIFT
            . PHP_EOL
            . <<<'SQL'
                WHERE s.status = :shift_status
                  AND e.status = :event_status
                  AND s.eind_op >= NOW()
                ORDER BY s.start_op ASC, s.shift_id ASC
                SQL
        );

        $statement->execute([
            'shift_status' => Shift::STATUS_ACTIEF,
            'event_status' => Event::STATUS_PUBLISHED,
        ]);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return Shift[]
     */
    public function findByEvent(
        int $eventId,
        bool $includeCancelled = true
    ): array {
        $sql = self::SELECT_SHIFT
            . PHP_EOL
            . 'WHERE s.event_id = :event_id';

        if (!$includeCancelled) {
            $sql .= PHP_EOL . 'AND s.status = :status';
        }

        $sql .= PHP_EOL
            . 'ORDER BY s.start_op ASC, s.shift_id ASC';

        $parameters = [
            'event_id' => $eventId,
        ];

        if (!$includeCancelled) {
            $parameters['status'] = Shift::STATUS_ACTIEF;
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function find(int $id): ?Shift
    {
        return $this->findUsingCondition(
            's.shift_id = :shift_id',
            [
                'shift_id' => $id,
            ]
        );
    }

    public function findVisibleToMembers(int $id): ?Shift
    {
        return $this->findUsingCondition(
            <<<'SQL'
                s.shift_id = :shift_id
                AND s.status = :shift_status
                AND e.status = :event_status
                SQL,
            [
                'shift_id' => $id,
                'shift_status' => Shift::STATUS_ACTIEF,
                'event_status' => Event::STATUS_PUBLISHED,
            ]
        );
    }

    public function lockForUpdate(int $id): ?Shift
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT
                s.*,
                e.titel AS event_titel,
                e.startdatum AS event_startdatum,
                e.einddatum AS event_einddatum,
                e.status AS event_status,
                st.naam AS type_naam,
                st.kleur AS type_kleur,
                st.icoon AS type_icoon,
                st.omschrijving AS type_omschrijving,
                0 AS aantal_wachtend,
                0 AS aantal_bevestigd,
                0 AS aantal_reserve,
                0 AS aantal_geweigerd,
                0 AS aantal_geannuleerd
            FROM shifts s
            INNER JOIN evenementen e
                ON e.event_id = s.event_id
            INNER JOIN shift_types st
                ON st.type_id = s.type_id
            WHERE s.shift_id = :shift_id
            LIMIT 1
            FOR UPDATE
            SQL);

        $statement->execute([
            'shift_id' => $id,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->fromDatabase($row)
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $parameters = $this->mapper->toDatabase($data);

        $statement = $this->database->prepare(<<<'SQL'
            INSERT INTO shifts
            (
                event_id,
                type_id,
                naam,
                start_op,
                eind_op,
                max_personen,
                status,
                aangemaakt_op,
                bijgewerkt_op
            )
            VALUES
            (
                :event_id,
                :type_id,
                :naam,
                :start_op,
                :eind_op,
                :max_personen,
                :status,
                NOW(),
                NULL
            )
            SQL);

        $statement->execute($parameters);

        return $this->database->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        int $id,
        array $data
    ): void {
        $parameters = $this->mapper->toDatabase($data);
        $parameters['shift_id'] = $id;

        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shifts
            SET
                event_id = :event_id,
                type_id = :type_id,
                naam = :naam,
                start_op = :start_op,
                eind_op = :eind_op,
                max_personen = :max_personen,
                status = :status,
                bijgewerkt_op = NOW()
            WHERE shift_id = :shift_id
            SQL);

        $statement->execute($parameters);
    }

    public function setStatus(
        int $id,
        string $status
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shifts
            SET
                status = :status,
                bijgewerkt_op = NOW()
            WHERE shift_id = :shift_id
            SQL);

        $statement->execute([
            'shift_id' => $id,
            'status' => $status,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->database->prepare(<<<'SQL'
            DELETE FROM shifts
            WHERE shift_id = :shift_id
            SQL);

        $statement->execute([
            'shift_id' => $id,
        ]);
    }

    public function countRegistrations(int $id): int
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM shift_inschrijvingen
            WHERE shift_id = :shift_id
            SQL);

        $statement->execute([
            'shift_id' => $id,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function countConfirmed(int $id): int
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT COUNT(*)
            FROM shift_inschrijvingen
            WHERE shift_id = :shift_id
              AND status = 'bevestigd'
            SQL);

        $statement->execute([
            'shift_id' => $id,
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function findUsingCondition(
        string $condition,
        array $parameters
    ): ?Shift {
        $statement = $this->database->prepare(
            self::SELECT_SHIFT
            . PHP_EOL
            . 'WHERE '
            . $condition
            . PHP_EOL
            . 'LIMIT 1'
        );

        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->fromDatabase($row)
            : null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return Shift[]
     */
    private function mapRows(array $rows): array
    {
        return array_map(
            fn(array $row): Shift => $this->mapper->fromDatabase($row),
            $rows
        );
    }
}
