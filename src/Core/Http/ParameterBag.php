<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

class ParameterBag
{
    /**
     * @var array<string, mixed>
     */
    private array $parameters;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function add(array $parameters): void
    {
        $this->parameters = array_replace($this->parameters, $parameters);
    }

    public function replace(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }

    public function clear(): void
    {
        $this->parameters = [];
    }

    public function count(): int
    {
        return count($this->parameters);
    }

    public function isEmpty(): bool
    {
        return $this->parameters === [];
    }

    public function first(): mixed
    {
        return reset($this->parameters);
    }

    public function last(): mixed
    {
        return end($this->parameters);
    }

    public function filter(callable $callback): array
    {
        return array_filter(
            $this->parameters,
            $callback,
            ARRAY_FILTER_USE_BOTH
        );
    }
}