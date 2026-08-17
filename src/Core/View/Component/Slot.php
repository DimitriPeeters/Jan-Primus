<?php

declare(strict_types=1);

namespace AEFS\Core\View\Component;

use AEFS\Core\View\Helper\HtmlAttributes;
use Stringable;

final class Slot implements Stringable
{
    private readonly HtmlAttributes $attributeBag;

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function __construct(
        private readonly string $content = '',
        array $attributes = []
    ) {
        $this->attributeBag = new HtmlAttributes($attributes);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function attributes(): HtmlAttributes
    {
        return $this->attributeBag;
    }

    public function attribute(
        string $name,
        mixed $default = null
    ): mixed {
        return $this->attributeBag->all()[$name] ?? $default;
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists(
            $name,
            $this->attributeBag->all()
        );
    }

    public function isEmpty(): bool
    {
        return trim($this->content) === '';
    }

    public function __toString(): string
    {
        return $this->content;
    }
}