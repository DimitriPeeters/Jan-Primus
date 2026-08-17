<?php

declare(strict_types=1);

namespace AEFS\Core\View\Component;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements ArrayAccess<string, Slot>
 * @implements IteratorAggregate<string, Slot>
 */
final class SlotBag implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array<string, Slot>
     */
    private array $slots = [];

    /**
     * @param array<string, Slot|string> $slots
     */
    public function __construct(array $slots = [])
    {
        foreach ($slots as $name => $slot) {
            $this->set(
                $name,
                $slot instanceof Slot ? $slot : new Slot($slot)
            );
        }
    }

    public function set(string $name, Slot $slot): void
    {
        $this->slots[$name] = $slot;
    }

    public function get(string $name, ?Slot $default = null): ?Slot
    {
        return $this->slots[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->slots);
    }

    /**
     * @return array<string, Slot>
     */
    public function all(): array
    {
        return $this->slots;
    }

    public function count(): int
    {
        return count($this->slots);
    }

    public function getIterator(): Traversable
    {
        yield from $this->slots;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    public function offsetGet(mixed $offset): ?Slot
    {
        if (!is_string($offset)) {
            return null;
        }

        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            return;
        }

        if ($value instanceof Slot) {
            $this->set($offset, $value);

            return;
        }

        $this->set($offset, new Slot((string) $value));
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            unset($this->slots[$offset]);
        }
    }
}