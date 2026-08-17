<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

use Stringable;

final class HtmlAttributes implements Stringable
{
    /**
     * @var array<string, scalar|bool|null>
     */
    private array $attributes;

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function set(
        string $name,
        string|int|float|bool|null $value
    ): self {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function remove(string $name): self
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    public function addClass(string ...$classes): self
    {
        $existing = (string) ($this->attributes['class'] ?? '');

        $classList = array_filter(
            [
                $existing,
                ...$classes,
            ],
            static fn (string $class): bool => trim($class) !== ''
        );

        return $this->set(
            'class',
            implode(' ', $classList)
        );
    }

    /**
     * @return array<string, scalar|bool|null>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    public function toHtml(): string
    {
        $compiled = [];

        foreach ($this->attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $compiled[] = $this->escape($name);

                continue;
            }

            $compiled[] = sprintf(
                '%s="%s"',
                $this->escape($name),
                $this->escape((string) $value)
            );
        }

        return $compiled === []
            ? ''
            : ' ' . implode(' ', $compiled);
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}