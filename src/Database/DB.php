<?php

declare(strict_types=1);

namespace AEFS\Database;

use AEFS\Database\Query\QueryBuilder;
use BadMethodCallException;

final class DB
{
    private static ?DatabaseManager $manager = null;

    private function __construct()
    {
    }

    public static function setManager(DatabaseManager $manager): void
    {
        self::$manager = $manager;
    }

    public static function manager(): DatabaseManager
    {
        if (self::$manager === null) {
            throw new BadMethodCallException(
                'DatabaseManager has not been registered.'
            );
        }

        return self::$manager;
    }

    public static function connection(?string $name = null): Connection
    {
        return self::manager()->connection($name);
    }

    public static function table(
        string $table,
        ?string $connection = null
    ): QueryBuilder {
        return self::manager()->table($table, $connection);
    }

    public static function select(
        string $sql,
        array $bindings = [],
        ?string $connection = null
    ): array {
        return self::connection($connection)
            ->select($sql, $bindings);
    }

    public static function first(
        string $sql,
        array $bindings = [],
        ?string $connection = null
    ): ?array {
        return self::connection($connection)
            ->first($sql, $bindings);
    }

    public static function statement(
        string $sql,
        array $bindings = [],
        ?string $connection = null
    ): bool {
        return self::connection($connection)
            ->statement($sql, $bindings);
    }

    public static function transaction(
        callable $callback,
        ?string $connection = null
    ): mixed {
        $db = self::connection($connection);

        $db->beginTransaction();

        try {
            $result = $callback($db);

            $db->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    public static function beginTransaction(
        ?string $connection = null
    ): bool {
        return self::connection($connection)
            ->beginTransaction();
    }

    public static function commit(
        ?string $connection = null
    ): bool {
        return self::connection($connection)
            ->commit();
    }

    public static function rollBack(
        ?string $connection = null
    ): bool {
        return self::connection($connection)
            ->rollBack();
    }

    public static function lastInsertId(
        ?string $connection = null
    ): string {
        return self::connection($connection)
            ->lastInsertId();
    }
}