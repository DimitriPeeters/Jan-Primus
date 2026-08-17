<?php

declare(strict_types=1);

namespace AEFS\Core;

final class RouteParameterBag
{
    /**
     * @var array<string, string>
     */
    private array $parameters = [];

    /**
     * @param array<string, string> $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->parameters[$key] ?? $default;
    }

    public function set(string $key, string $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function add(array $parameters): self
    {
        foreach ($parameters as $key => $value) {
            $this->parameters[(string) $key] = (string) $value;
        }

        return $this;
    }

    public function remove(string $key): self
    {
        unset($this->parameters[$key]);

        return $this;
    }

    public function replace(array $parameters): self
    {
        $this->parameters = [];

        return $this->add($parameters);
    }

    public function clear(): self
    {
        $this->parameters = [];

        return $this;
    }

    public function count(): int
    {
        return count($this->parameters);
    }

    public function isEmpty(): bool
    {
        return $this->parameters === [];
    }

    public function first(): ?string
    {
        return $this->parameters === []
            ? null
            : reset($this->parameters);
    }

    public function last(): ?string
    {
        return $this->parameters === []
            ? null
            : end($this->parameters);
    }
}
