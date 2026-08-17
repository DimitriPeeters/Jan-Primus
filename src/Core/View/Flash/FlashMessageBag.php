<?php

declare(strict_types=1);

namespace AEFS\Core\View\Flash;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, FlashMessage>
 */
final class FlashMessageBag implements Countable, IteratorAggregate
{
    /**
     * @var list<FlashMessage>
     */
    private array $messages = [];

    public function add(string $type, string $message): void
    {
        $type = trim($type);
        $message = trim($message);

        if ($type === '' || $message === '') {
            return;
        }

        $this->messages[] = new FlashMessage($type, $message);
    }

    /**
     * @return list<FlashMessage>
     */
    public function all(): array
    {
        return $this->messages;
    }

    public function count(): int
    {
        return count($this->messages);
    }

    public function isEmpty(): bool
    {
        return $this->messages === [];
    }

    public function getIterator(): Traversable
    {
        yield from $this->messages;
    }
}