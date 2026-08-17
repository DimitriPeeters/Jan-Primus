<?php

declare(strict_types=1);

namespace AEFS\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

final class Database
{
    private readonly PDO $pdo;

    public function __construct(Config $config)
    {
        $defaultConnection = (string) $config->get(
            'database.default',
            'mysql'
        );

        $connection = $config->get(
            'database.connections.' . $defaultConnection,
            []
        );

        if (!is_array($connection)) {
            throw new RuntimeException(
                sprintf(
                    'Database connection [%s] is not configured.',
                    $defaultConnection
                )
            );
        }

        $driver = (string) ($connection['driver'] ?? 'mysql');
        $host = (string) ($connection['host'] ?? 'localhost');
        $port = (int) ($connection['port'] ?? 3306);
        $database = trim(
            (string) ($connection['database'] ?? '')
        );
        $username = trim(
            (string) ($connection['username'] ?? '')
        );
        $password = (string) ($connection['password'] ?? '');
        $charset = (string) ($connection['charset'] ?? 'utf8mb4');

        if ($database === '' || $username === '') {
            throw new RuntimeException(
                sprintf(
                    'Database configuration for connection [%s] is incomplete.',
                    $defaultConnection
                )
            );
        }

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $driver,
            $host,
            $port,
            $database,
            $charset
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database connection failed: '
                . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function prepare(string $sql): PDOStatement
    {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database statement preparation failed: '
                . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    public function query(string $sql): PDOStatement
    {
        try {
            $statement = $this->pdo->query($sql);

            if (!$statement instanceof PDOStatement) {
                throw new RuntimeException(
                    'Database query did not return a valid statement.'
                );
            }

            return $statement;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database query failed: '
                . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    /**
     * @param array<string|int, mixed> $parameters
     */
    public function execute(
        string $sql,
        array $parameters = []
    ): PDOStatement {
        $statement = $this->prepare($sql);

        try {
            $statement->execute($parameters);
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Database statement execution failed: '
                . $exception->getMessage(),
                0,
                $exception
            );
        }

        return $statement;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function transaction(callable $callback): mixed
    {
        $ownsTransaction = !$this->pdo->inTransaction();

        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $result = $callback($this);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $result;
        } catch (Throwable $throwable) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }
}
