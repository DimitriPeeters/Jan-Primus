<?php

declare(strict_types=1);

namespace AEFS\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;

    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $host = $config['host'];
        $db   = $config['database'];
        $user = $config['username'];
        $pass = $config['password'];
        $charset = $config['charset'];

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

        try {

            $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

        } catch (PDOException $e) {

            throw new RuntimeException(
                'Databaseverbinding mislukt: ' . $e->getMessage()
            );

        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function query(string $sql)
{
    return $this->pdo->query($sql);
}

public function prepare(string $sql)
{
    return $this->pdo->prepare($sql);
}
}