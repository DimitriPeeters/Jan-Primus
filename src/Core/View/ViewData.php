<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final class ViewData implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data = []
    ) {
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->data
        );
    }

    public function set(
        string $key,
        mixed $value
    ): self {
        $clone = clone $this;
        $clone->data[$key] = $value;

        return $clone;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function merge(array $data): self
    {
        $clone = clone $this;
        $clone->data = array_replace(
            $clone->data,
            $data
        );

        return $clone;
    }

    public function remove(string $key): self
    {
        $clone = clone $this;
        unset($clone->data[$key]);

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): Traversable
    {
        yield from $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset)
            && $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset)) {
            return null;
        }

        return $this->get($offset);
    }

    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        if (!is_string($offset)) {
            return;
        }

        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            unset($this->data[$offset]);
        }
    }
}