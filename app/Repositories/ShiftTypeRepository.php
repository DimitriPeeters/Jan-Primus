<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\Database;
use App\Mappers\ShiftTypeMapper;
use App\Models\ShiftType;
use PDO;
use RuntimeException;

final class ShiftTypeRepository
{
    public function __construct(
        private readonly Database $database,
        private readonly ShiftTypeMapper $mapper
    ) {
    }

    /**
     * @return ShiftType[]
     */
    public function all(): array
    {
        $statement = $this->database->query(<<<'SQL'
            SELECT *
            FROM shift_types
            ORDER BY actief DESC, naam ASC
            SQL);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return array<int, array{type: ShiftType, shift_count: int}>
     */
    public function allWithShiftCounts(): array
    {
        $rows = $this->database->query(<<<'SQL'
            SELECT
                st.*,
                COUNT(s.shift_id) AS shift_count
            FROM shift_types AS st
            LEFT JOIN shifts AS s
                ON s.type_id = st.type_id
            GROUP BY st.type_id
            ORDER BY st.actief DESC, st.naam ASC
            SQL)->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $row): array => [
                'type' => $this->mapper->fromDatabase($row),
                'shift_count' => (int) $row['shift_count'],
            ],
            $rows
        );
    }

    /**
     * @return ShiftType[]
     */
    public function active(): array
    {
        $statement = $this->database->query(<<<'SQL'
            SELECT *
            FROM shift_types
            WHERE actief = 1
            ORDER BY naam ASC
            SQL);

        return $this->mapRows(
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function find(int $id): ?ShiftType
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT *
            FROM shift_types
            WHERE type_id = :type_id
            LIMIT 1
            SQL);

        $statement->execute([
            'type_id' => $id,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->mapper->fromDatabase($row)
            : null;
    }

    public function findByName(string $name): ?ShiftType
    {
        $statement = $this->database->prepare(<<<'SQL'
            SELECT *
            FROM shift_types
            WHERE naam = :naam
            LIMIT 1
            SQL);

        $statement->execute([
            'naam' => trim($name),
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
            INSERT INTO shift_types
            (
                naam,
                kleur,
                icoon,
                omschrijving,
                actief,
                aangemaakt_op,
                bijgewerkt_op
            )
            VALUES
            (
                :naam,
                :kleur,
                :icoon,
                :omschrijving,
                :actief,
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
        $parameters['type_id'] = $id;

        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shift_types
            SET
                naam = :naam,
                kleur = :kleur,
                icoon = :icoon,
                omschrijving = :omschrijving,
                actief = :actief,
                bijgewerkt_op = NOW()
            WHERE type_id = :type_id
            SQL);

        $statement->execute($parameters);
    }

    public function setActive(
        int $id,
        bool $active
    ): void {
        $statement = $this->database->prepare(<<<'SQL'
            UPDATE shift_types
            SET
                actief = :actief,
                bijgewerkt_op = NOW()
            WHERE type_id = :type_id
            SQL);

        $statement->execute([
            'type_id' => $id,
            'actief' => $active ? 1 : 0,
        ]);
    }

    public function existsByName(
        string $name,
        ?int $excludeId = null
    ): bool {
        $sql = <<<'SQL'
            SELECT COUNT(*)
            FROM shift_types
            WHERE naam = :naam
            SQL;

        $parameters = [
            'naam' => trim($name),
        ];

        if ($excludeId !== null) {
            $sql .= PHP_EOL . 'AND type_id <> :type_id';
            $parameters['type_id'] = $excludeId;
        }

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }

    public function ensureDefault(): ShiftType
    {
        $type = $this->findByName(
            ShiftType::DEFAULT_NAME
        );

        if ($type !== null) {
            return $type;
        }

        $id = $this->create([
            'naam' => ShiftType::DEFAULT_NAME,
            'kleur' => ShiftType::DEFAULT_COLOR,
            'icoon' => 'users',
            'omschrijving' => 'Standaardfunctie voor vrijwilligers',
            'actief' => true,
        ]);

        return $this->find($id)
            ?? throw new RuntimeException(
                'Het standaard shifttype kon niet worden aangemaakt.'
            );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return ShiftType[]
     */
    private function mapRows(array $rows): array
    {
        return array_map(
            fn(array $row): ShiftType => $this->mapper->fromDatabase($row),
            $rows
        );
    }
}
