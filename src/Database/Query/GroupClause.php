<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

final class GroupClause
{
    /**
     * @var array<int, string|Expression>
     */
    private array $groups = [];

    public function groupBy(string|Expression ...$columns): self
    {
        foreach ($columns as $column) {
            $this->groups[] = $column;
        }

        return $this;
    }

    public function clear(): self
    {
        $this->groups = [];

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->groups === [];
    }

    /**
     * @return array<int, string|Expression>
     */
    public function groups(): array
    {
        return $this->groups;
    }

    public function compile(Grammar $grammar): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        return implode(
            ', ',
            array_map(
                static fn (string|Expression $column): string => $grammar->wrap($column),
                $this->groups
            )
        );
    }
}