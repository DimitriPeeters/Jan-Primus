<?php

declare(strict_types=1);

namespace AEFS\Core;

use PDO;
use RuntimeException;

abstract class BaseRepository
{
    protected Database $database;

    protected string $table;

    protected string $primaryKey = 'id';

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function all(
        string $orderBy = '',
        string $direction = 'ASC'
    ): array {
        $sql = "SELECT * FROM {$this->table}";

        if ($orderBy !== '') {
            $direction = strtoupper($direction);

            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                $direction = 'ASC';
            }

            $sql .= " ORDER BY {$orderBy} {$direction}";
        }

        return array_map(
            [$this, 'map'],
            $this->fetchAll($sql)
        );
    }

    public function find(int $id): mixed
    {
        $row = $this->fetch("
            SELECT *
            FROM {$this->table}
            WHERE {$this->primaryKey} = :id
            LIMIT 1
        ", [
            'id' => $id,
        ]);

        return $row ? $this->map($row) : null;
    }

    public function findBy(array $criteria): array
    {
        [$where, $params] = $this->buildWhereClause($criteria);

        return array_map(
            [$this, 'map'],
            $this->fetchAll("
                SELECT *
                FROM {$this->table}
                {$where}
            ", $params)
        );
    }

    public function findOneBy(array $criteria): mixed
    {
        [$where, $params] = $this->buildWhereClause($criteria);

        $row = $this->fetch("
            SELECT *
            FROM {$this->table}
            {$where}
            LIMIT 1
        ", $params);

        return $row ? $this->map($row) : null;
    }

    public function exists(array|int $criteria): bool
    {
        if (is_int($criteria)) {
            $criteria = [
                $this->primaryKey => $criteria,
            ];
        }

        [$where, $params] = $this->buildWhereClause($criteria);

        $row = $this->fetch("
            SELECT COUNT(*) AS aantal
            FROM {$this->table}
            {$where}
        ", $params);

        return (int) ($row['aantal'] ?? 0) > 0;
    }

    public function count(array $criteria = []): int
    {
        [$where, $params] = $this->buildWhereClause($criteria);

        $row = $this->fetch("
            SELECT COUNT(*) AS aantal
            FROM {$this->table}
            {$where}
        ", $params);

        return (int) ($row['aantal'] ?? 0);
    }

    public function insert(array $data): int
    {
        if ($data === []) {
            throw new RuntimeException('Insert data mag niet leeg zijn.');
        }

        $columns = array_keys($data);

        $columnSql = implode(', ', $columns);
        $valueSql = implode(', ', array_map(
            static fn (string $column): string => ':' . $column,
            $columns
        ));

        $this->execute("
            INSERT INTO {$this->table}
            ({$columnSql})
            VALUES
            ({$valueSql})
        ", $data);

        return $this->lastInsertId();
    }

    public function updateById(
        int $id,
        array $data
    ): bool {
        if ($data === []) {
            return false;
        }

        $setSql = implode(', ', array_map(
            static fn (string $column): string => "{$column} = :{$column}",
            array_keys($data)
        ));

        $data['id'] = $id;

        return $this->execute("
            UPDATE {$this->table}
            SET {$setSql}
            WHERE {$this->primaryKey} = :id
        ", $data);
    }

    public function delete(int $id): void
    {
        $this->deleteById($id);
    }

    public function deleteById(int $id): bool
    {
        return $this->execute("
            DELETE
            FROM {$this->table}
            WHERE {$this->primaryKey} = :id
        ", [
            'id' => $id,
        ]);
    }

    public function beginTransaction(): bool
    {
        return $this->database->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->database->commit();
    }

    public function rollBack(): bool
    {
        return $this->database->rollback();
    }

    protected function fetchAll(
        string $sql,
        array $params = []
    ): array {
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetch(
        string $sql,
        array $params = []
    ): ?array {
        $stmt = $this->database->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    protected function execute(
        string $sql,
        array $params = []
    ): bool {
        $stmt = $this->database->prepare($sql);

        return $stmt->execute($params);
    }

    protected function lastInsertId(): int
    {
        return (int) $this->database
            ->pdo()
            ->lastInsertId();
    }

    protected function buildWhereClause(array $criteria): array
    {
        if ($criteria === []) {
            return ['', []];
        }

        $where = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $param = str_replace('.', '_', (string) $column);

            if ($value === null) {
                $where[] = "{$column} IS NULL";
                continue;
            }

            $where[] = "{$column} = :{$param}";
            $params[$param] = $value;
        }

        return [
            'WHERE ' . implode(' AND ', $where),
            $params,
        ];
    }

    abstract protected function map(array $row): mixed;
}