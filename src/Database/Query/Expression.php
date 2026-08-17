<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

use Stringable;

final readonly class Expression implements Stringable
{
    public function __construct(
        private string $value
    ) {
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function raw(string $expression): self
    {
        return new self($expression);
    }
}