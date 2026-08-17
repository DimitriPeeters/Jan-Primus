<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

final class HavingClause
{
    /**
     * @var array<int, array{
     *     boolean:string,
     *     column:string|Expression,
     *     operator:string,
     *     value:mixed
     * }>
     */
    private array $havings = [];

    public function having(
        string|Expression $column,
        string $operator,
        mixed $value,
        string $boolean = 'AND'
    ): self {
        $this->havings[] = [
            'boolean' => strtoupper($boolean),
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
        ];

        return $this;
    }

    public function orHaving(
        string|Expression $column,
        string $operator,
        mixed $value
    ): self {
        return $this->having(
            $column,
            $operator,
            $value,
            'OR'
        );
    }

    public function clear(): self
    {
        $this->havings = [];

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->havings === [];
    }

    /**
     * @return array<int, mixed>
     */
    public function bindings(): array
    {
        $bindings = [];

        foreach ($this->havings as $having) {
            if (!$having['value'] instanceof Expression) {
                $bindings[] = $having['value'];
            }
        }

        return $bindings;
    }

    public function compile(Grammar $grammar): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $compiled = [];

        foreach ($this->havings as $index => $having) {

            $sql = sprintf(
                '%s %s %s',
                $grammar->wrap($having['column']),
                $having['operator'],
                $grammar->parameter($having['value'])
            );

            if ($index > 0) {
                $sql = $having['boolean'] . ' ' . $sql;
            }

            $compiled[] = $sql;
        }

        return implode(' ', $compiled);
    }
}