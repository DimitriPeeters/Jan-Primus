<?php

declare(strict_types=1);

namespace AEFS\Database;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

final class Connection
{
    private PDO $pdo;

    /**
     * @param array{
     *     driver?:string,
     *     host:string,
     *     port?:int,
     *     database:string,
     *     username:string,
     *     password:string,
     *     charset?:string,
     *     collation?:string,
     *     options?:array<int,mixed>
     * } $config
     */
    public function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $driver,
            $config['host'],
            (int) ($config['port'] ?? 3306),
            $config['database'],
            $charset
        );

        $options = $config['options'] ?? [];

        $options += [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $options
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Unable to connect to the database.',
                previous: $exception
            );
        }

        if (isset($config['collation'])) {
            $this->pdo->exec(
                sprintf(
                    "SET NAMES '%s' COLLATE '%s'",
                    $charset,
                    $config['collation']
                )
            );
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function query(string $sql): PDOStatement
    {
        return $this->pdo->query($sql);
    }

    /**
     * @param array<int|string,mixed> $bindings
     */
    public function execute(string $sql, array $bindings = []): PDOStatement
    {
        $statement = $this->prepare($sql);

        $statement->execute($bindings);

        return $statement;
    }

    /**
     * @param array<int|string,mixed> $bindings
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this
            ->execute($sql, $bindings)
            ->fetchAll();
    }

    /**
     * @param array<int|string,mixed> $bindings
     */
    public function first(string $sql, array $bindings = []): array|null
    {
        $row = $this
            ->execute($sql, $bindings)
            ->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string,mixed> $bindings
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        return $this
            ->execute($sql, $bindings)
            ->rowCount() >= 0;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}