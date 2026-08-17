<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

final class WhereClause
{
    /**
     * @var array<int, array{
     *     type:string,
     *     boolean:string,
     *     column:string|Expression,
     *     operator:string,
     *     value:mixed
     * }>
     */
    private array $clauses = [];

    public function where(
        string|Expression $column,
        string $operator,
        mixed $value,
        string $boolean = 'AND'
    ): self {
        $this->clauses[] = [
            'type'     => 'basic',
            'boolean'  => strtoupper($boolean),
            'column'   => $column,
            'operator' => strtoupper($operator),
            'value'    => $value,
        ];

        return $this;
    }

    public function orWhere(
        string|Expression $column,
        string $operator,
        mixed $value
    ): self {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereNull(
        string|Expression $column,
        string $boolean = 'AND'
    ): self {
        $this->clauses[] = [
            'type'     => 'null',
            'boolean'  => strtoupper($boolean),
            'column'   => $column,
            'operator' => '',
            'value'    => null,
        ];

        return $this;
    }

    public function orWhereNull(string|Expression $column): self
    {
        return $this->whereNull($column, 'OR');
    }

    public function whereNotNull(
        string|Expression $column,
        string $boolean = 'AND'
    ): self {
        $this->clauses[] = [
            'type'     => 'not_null',
            'boolean'  => strtoupper($boolean),
            'column'   => $column,
            'operator' => '',
            'value'    => null,
        ];

        return $this;
    }

    public function orWhereNotNull(string|Expression $column): self
    {
        return $this->whereNotNull($column, 'OR');
    }

    public function whereIn(
        string|Expression $column,
        array $values,
        string $boolean = 'AND',
        bool $not = false
    ): self {
        $this->clauses[] = [
            'type'     => $not ? 'not_in' : 'in',
            'boolean'  => strtoupper($boolean),
            'column'   => $column,
            'operator' => '',
            'value'    => array_values($values),
        ];

        return $this;
    }

    public function orWhereIn(
        string|Expression $column,
        array $values
    ): self {
        return $this->whereIn($column, $values, 'OR');
    }

    public function whereNotIn(
        string|Expression $column,
        array $values,
        string $boolean = 'AND'
    ): self {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function orWhereNotIn(
        string|Expression $column,
        array $values
    ): self {
        return $this->whereNotIn($column, $values, 'OR');
    }

    public function whereBetween(
        string|Expression $column,
        array $values,
        string $boolean = 'AND',
        bool $not = false
    ): self {
        $this->clauses[] = [
            'type'     => $not ? 'not_between' : 'between',
            'boolean'  => strtoupper($boolean),
            'column'   => $column,
            'operator' => '',
            'value'    => [
                $values[0] ?? null,
                $values[1] ?? null,
            ],
        ];

        return $this;
    }

    public function orWhereBetween(
        string|Expression $column,
        array $values
    ): self {
        return $this->whereBetween($column, $values, 'OR');
    }

    public function whereNotBetween(
        string|Expression $column,
        array $values,
        string $boolean = 'AND'
    ): self {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function orWhereNotBetween(
        string|Expression $column,
        array $values
    ): self {
        return $this->whereNotBetween($column, $values, 'OR');
    }

    public function whereLike(
        string|Expression $column,
        string $value,
        string $boolean = 'AND'
    ): self {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    public function orWhereLike(
        string|Expression $column,
        string $value
    ): self {
        return $this->whereLike($column, $value, 'OR');
    }

    public function isEmpty(): bool
    {
        return $this->clauses === [];
    }

    /**
     * @return array<int, array{
     *     type:string,
     *     boolean:string,
     *     column:string|Expression,
     *     operator:string,
     *     value:mixed
     * }>
     */
    public function clauses(): array
    {
        return $this->clauses;
    }

    /**
     * @return array<int, mixed>
     */
    public function bindings(): array
    {
        $bindings = [];

        foreach ($this->clauses as $clause) {
            switch ($clause['type']) {
                case 'basic':
                    if (!$clause['value'] instanceof Expression) {
                        $bindings[] = $clause['value'];
                    }
                    break;

                case 'in':
                case 'not_in':
                    foreach ($clause['value'] as $value) {
                        if (!$value instanceof Expression) {
                            $bindings[] = $value;
                        }
                    }
                    break;

                case 'between':
                case 'not_between':
                    if (!$clause['value'][0] instanceof Expression) {
                        $bindings[] = $clause['value'][0];
                    }

                    if (!$clause['value'][1] instanceof Expression) {
                        $bindings[] = $clause['value'][1];
                    }
                    break;
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

        foreach ($this->clauses as $index => $clause) {
            $sql = match ($clause['type']) {
                'basic' => sprintf(
                    '%s %s %s',
                    $grammar->wrap($clause['column']),
                    $clause['operator'],
                    $grammar->parameter($clause['value'])
                ),

                'null' => sprintf(
                    '%s IS NULL',
                    $grammar->wrap($clause['column'])
                ),

                'not_null' => sprintf(
                    '%s IS NOT NULL',
                    $grammar->wrap($clause['column'])
                ),

                'in', 'not_in' => sprintf(
                    '%s %sIN (%s)',
                    $grammar->wrap($clause['column']),
                    $clause['type'] === 'not_in' ? 'NOT ' : '',
                    $this->compileValueList($grammar, $clause['value'])
                ),

                'between', 'not_between' => sprintf(
                    '%s %sBETWEEN %s AND %s',
                    $grammar->wrap($clause['column']),
                    $clause['type'] === 'not_between' ? 'NOT ' : '',
                    $grammar->parameter($clause['value'][0]),
                    $grammar->parameter($clause['value'][1])
                ),

                default => '',
            };

            if ($index > 0) {
                $sql = $clause['boolean'] . ' ' . $sql;
            }

            $compiled[] = $sql;
        }

        return implode(' ', $compiled);
    }

    /**
     * @param array<int, mixed> $values
     */
    private function compileValueList(Grammar $grammar, array $values): string
    {
        if ($values === []) {
            return 'NULL';
        }

        return implode(
            ', ',
            array_map(
                static fn (mixed $value): string => $grammar->parameter($value),
                $values
            )
        );
    }
}