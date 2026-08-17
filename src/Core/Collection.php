<?php

declare(strict_types=1);

namespace AEFS\Core;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

final class Collection implements IteratorAggregate, Countable
{
    /**
     * @var array<int, mixed>
     */
    private array $items;

    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function last(): mixed
    {
        if ($this->items === []) {
            return null;
        }

        return $this->items[array_key_last($this->items)];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(?callable $callback = null): self
    {
        return new self(array_values(array_filter(
            $this->items,
            $callback
        )));
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    public function find(callable $callback): mixed
    {
        foreach ($this->items as $item) {

            if ($callback($item)) {
                return $item;
            }

        }

        return null;
    }

    public function pluck(string $property): self
    {
        $values = [];

        foreach ($this->items as $item) {

            if (is_array($item)) {

                $values[] = $item[$property] ?? null;

                continue;
            }

            if (isset($item->$property)) {
                $values[] = $item->$property;
            }

        }

        return new self($values);
    }

    public function sort(callable $callback): self
    {
        $items = $this->items;

        usort($items, $callback);

        return new self($items);
    }

    public function values(): self
    {
        return new self(array_values($this->items));
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}