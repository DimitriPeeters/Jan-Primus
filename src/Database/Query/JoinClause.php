<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

final class JoinClause
{
    /**
     * @var array<int, array{
     *     boolean:string,
     *     first:string|Expression,
     *     operator:string,
     *     second:string|Expression
     * }>
     */
    private array $conditions = [];

    public function __construct(
        private readonly string $type,
        private readonly string|Expression $table
    ) {
    }

    public function on(
        string|Expression $first,
        string $operator,
        string|Expression $second,
        string $boolean = 'AND'
    ): self {
        $this->conditions[] = [
            'boolean' => strtoupper($boolean),
            'first'   => $first,
            'operator'=> strtoupper($operator),
            'second'  => $second,
        ];

        return $this;
    }

    public function orOn(
        string|Expression $first,
        string $operator,
        string|Expression $second
    ): self {
        return $this->on(
            $first,
            $operator,
            $second,
            'OR'
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function table(): string|Expression
    {
        return $this->table;
    }

    /**
     * @return array<int, array{
     *     boolean:string,
     *     first:string|Expression,
     *     operator:string,
     *     second:string|Expression
     * }>
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    public function compile(Grammar $grammar): string
    {
        $sql = sprintf(
            '%s JOIN %s',
            strtoupper($this->type),
            $grammar->wrapTable($this->table)
        );

        if ($this->conditions === []) {
            return $sql;
        }

        $sql .= ' ON ';

        foreach ($this->conditions as $index => $condition) {

            if ($index > 0) {
                $sql .= ' ' . $condition['boolean'] . ' ';
            }

            $sql .= sprintf(
                '%s %s %s',
                $grammar->wrap($condition['first']),
                $condition['operator'],
                $grammar->wrap($condition['second'])
            );
        }

        return $sql;
    }
}