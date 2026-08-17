<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<string, list<string>>
 */
final class ErrorBag implements Countable, IteratorAggregate
{
    /**
     * @var array<string, list<string>>
     */
    private array $errors = [];

    /**
     * @param array<string, string|list<string>> $errors
     */
    public function __construct(array $errors = [])
    {
        foreach ($errors as $field => $messages) {
            $this->addMany(
                $field,
                is_array($messages) ? $messages : [$messages]
            );
        }
    }

    public function add(string $field, string $message): void
    {
        $field = trim($field);
        $message = trim($message);

        if ($field === '' || $message === '') {
            return;
        }

        $this->errors[$field] ??= [];

        if (!in_array($message, $this->errors[$field], true)) {
            $this->errors[$field][] = $message;
        }
    }

    /**
     * @param list<string> $messages
     */
    public function addMany(
        string $field,
        array $messages
    ): void {
        foreach ($messages as $message) {
            $this->add($field, $message);
        }
    }

    public function has(string $field): bool
    {
        return isset($this->errors[$field])
            && $this->errors[$field] !== [];
    }

    public function any(): bool
    {
        return $this->errors !== [];
    }

    public function first(
        string $field,
        string $default = ''
    ): string {
        return $this->errors[$field][0] ?? $default;
    }

    /**
     * @return list<string>
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * @return list<string>
     */
    public function flatten(): array
    {
        $messages = [];

        foreach ($this->errors as $fieldMessages) {
            foreach ($fieldMessages as $message) {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    public function count(): int
    {
        return count($this->flatten());
    }

    public function getIterator(): Traversable
    {
        yield from $this->errors;
    }
}