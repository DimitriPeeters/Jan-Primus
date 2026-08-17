<?php

declare(strict_types=1);

namespace AEFS\Core;

abstract class Model
{
    /**
     * @var array<string,mixed>
     */
    protected array $attributes = [];

    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function remove(string $key): static
    {
        unset($this->attributes[$key]);

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key): void
    {
        $this->remove($key);
    }

    public function __debugInfo(): array
    {
        return $this->attributes;
    }
}