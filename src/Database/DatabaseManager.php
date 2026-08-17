<?php

declare(strict_types=1);

namespace AEFS\Database;

use AEFS\Database\Query\MySqlGrammar;
use AEFS\Database\Query\QueryBuilder;
use InvalidArgumentException;

final class DatabaseManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configurations = [];

    /**
     * @var array<string, Connection>
     */
    private array $connections = [];

    private string $defaultConnection = 'default';

    /**
     * @param array{
     *     default?: string,
     *     connections?: array<string, array<string, mixed>>
     * } $config
     */
    public function __construct(array $config = [])
    {
        $this->defaultConnection = (string) ($config['default'] ?? 'default');

        foreach (($config['connections'] ?? []) as $name => $configuration) {
            $this->addConnection($name, $configuration);
        }
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function addConnection(string $name, array $configuration): self
    {
        $this->configurations[$name] = $configuration;

        return $this;
    }

    public function hasConnection(string $name): bool
    {
        return isset($this->configurations[$name]);
    }

    public function setDefaultConnection(string $name): self
    {
        if (!$this->hasConnection($name)) {
            throw new InvalidArgumentException(
                sprintf('Database connection [%s] is not configured.', $name)
            );
        }

        $this->defaultConnection = $name;

        return $this;
    }

    public function defaultConnection(): string
    {
        return $this->defaultConnection;
    }

    public function connection(?string $name = null): Connection
    {
        $name ??= $this->defaultConnection;

        if (!$this->hasConnection($name)) {
            throw new InvalidArgumentException(
                sprintf('Database connection [%s] is not configured.', $name)
            );
        }

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = new Connection(
                $this->configurations[$name]
            );
        }

        return $this->connections[$name];
    }

    public function table(string $table, ?string $connection = null): QueryBuilder
    {
        return new QueryBuilder(
            connection: $this->connection($connection),
            grammar: new MySqlGrammar(),
            table: $table
        );
    }

    public function reconnect(?string $name = null): Connection
    {
        $name ??= $this->defaultConnection;

        $this->disconnect($name);

        return $this->connection($name);
    }

    public function disconnect(?string $name = null): void
    {
        $name ??= $this->defaultConnection;

        unset($this->connections[$name]);
    }

    public function purge(): void
    {
        $this->connections = [];
    }

    /**
     * @return array<string, Connection>
     */
    public function activeConnections(): array
    {
        return $this->connections;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function configurations(): array
    {
        return $this->configurations;
    }
}