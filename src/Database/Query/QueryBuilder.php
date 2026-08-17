<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

use AEFS\Database\Connection;

final class QueryBuilder
{
    private array $columns = ['*'];

    private bool $distinct = false;

    private ?int $limit = null;

    private ?int $offset = null;

    private readonly WhereClause $whereClause;

    private readonly OrderClause $orderClause;

    private readonly GroupClause $groupClause;

    private readonly HavingClause $havingClause;

    /**
     * @var JoinClause[]
     */
    private array $joins = [];

    /**
     * @var array<int, mixed>
     */
    private array $bindings = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly Grammar $grammar,
        private readonly string $table
    ) {
        $this->whereClause = new WhereClause();
        $this->orderClause = new OrderClause();
        $this->groupClause = new GroupClause();
        $this->havingClause = new HavingClause();
    }

    public function table(): string
    {
        return $this->table;
    }

    public function distinct(): bool
    {
        return $this->distinct;
    }

    public function select(string|Expression ...$columns): self
    {
        if ($columns !== []) {
            $this->columns = $columns;
        }

        return $this;
    }

    public function addSelect(string|Expression ...$columns): self
    {
        array_push($this->columns, ...$columns);

        return $this;
    }

    public function setDistinct(bool $distinct = true): self
    {
        $this->distinct = $distinct;

        return $this;
    }

    public function where(
        string|Expression $column,
        string $operator,
        mixed $value
    ): self {
        $this->whereClause->where($column, $operator, $value);

        return $this;
    }

    public function orWhere(
        string|Expression $column,
        string $operator,
        mixed $value
    ): self {
        $this->whereClause->orWhere($column, $operator, $value);

        return $this;
    }

    public function whereNull(string|Expression $column): self
    {
        $this->whereClause->whereNull($column);

        return $this;
    }

    public function whereNotNull(string|Expression $column): self
    {
        $this->whereClause->whereNotNull($column);

        return $this;
    }

    public function join(
        string|Expression $table,
        string|Expression $first,
        string $operator,
        string|Expression $second,
        string $type = 'INNER'
    ): self {
        $join = new JoinClause($type, $table);

        $join->on($first, $operator, $second);

        $this->joins[] = $join;

        return $this;
    }

    public function leftJoin(
        string|Expression $table,
        string|Expression $first,
        string $operator,
        string|Expression $second
    ): self {
        return $this->join(
            $table,
            $first,
            $operator,
            $second,
            'LEFT'
        );
    }

    public function rightJoin(
        string|Expression $table,
        string|Expression $first,
        string $operator,
        string|Expression $second
    ): self {
        return $this->join(
            $table,
            $first,
            $operator,
            $second,
            'RIGHT'
        );
    }

    public function groupBy(string|Expression ...$columns): self
    {
        $this->groupClause->groupBy(...$columns);

        return $this;
    }

    public function having(
        string|Expression $column,
        string $operator,
        mixed $value
    ): self {
        $this->havingClause->having($column, $operator, $value);

        return $this;
    }

    public function orderBy(
        string|Expression $column,
        string $direction = 'ASC'
    ): self {
        $this->orderClause->orderBy($column, $direction);

        return $this;
    }

    public function latest(string|Expression $column = 'created_at'): self
    {
        $this->orderClause->latest($column);

        return $this;
    }

    public function oldest(string|Expression $column = 'created_at'): self
    {
        $this->orderClause->oldest($column);

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return array<int, string|Expression>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return JoinClause[]
     */
    public function joins(): array
    {
        return $this->joins;
    }

    public function whereClause(): WhereClause
    {
        return $this->whereClause;
    }

    public function orderClause(): OrderClause
    {
        return $this->orderClause;
    }

    public function groupClause(): GroupClause
    {
        return $this->groupClause;
    }

    public function havingClause(): HavingClause
    {
        return $this->havingClause;
    }

    public function limitValue(): ?int
    {
        return $this->limit;
    }

    public function offsetValue(): ?int
    {
        return $this->offset;
    }

    /**
     * @return array<int, mixed>
     */
    public function bindings(): array
    {
        return array_merge(
            $this->whereClause->bindings(),
            $this->havingClause->bindings(),
            $this->bindings
        );
    }

    public function toSql(): string
    {
        return $this->grammar->compileSelect($this);
    }

    public function get(): array
    {
        return $this->connection->select(
            $this->toSql(),
            $this->bindings()
        );
    }

    public function first(): ?array
    {
        $this->limit(1);

        return $this->connection->first(
            $this->toSql(),
            $this->bindings()
        );
    }

    public function find(int|string $id, string $column = 'id'): ?array
{
    return $this
        ->where($column, '=', $id)
        ->first();
}

public function exists(): bool
{
    return $this->count() > 0;
}

public function count(string $column = '*'): int
{
    $builder = clone $this;

    $builder->select(
        Expression::raw(sprintf(
            'COUNT(%s) AS aggregate',
            $column === '*' ? '*' : $this->grammar->wrap($column)
        ))
    );

    $row = $builder->first();

    return (int) ($row['aggregate'] ?? 0);
}

public function sum(string $column): float|int
{
    return $this->aggregate('SUM', $column);
}

public function avg(string $column): float|int
{
    return $this->aggregate('AVG', $column);
}

public function min(string $column): mixed
{
    return $this->aggregate('MIN', $column);
}

public function max(string $column): mixed
{
    return $this->aggregate('MAX', $column);
}

private function aggregate(
    string $function,
    string $column
): mixed {
    $builder = clone $this;

    $builder->select(
        Expression::raw(
            sprintf(
                '%s(%s) AS aggregate',
                strtoupper($function),
                $this->grammar->wrap($column)
            )
        )
    );

    $row = $builder->first();

    return $row['aggregate'] ?? null;
}

public function insert(array $values): bool
{
    $sql = $this->grammar->compileInsert(
        $this->table,
        $values
    );

    return $this->connection->statement(
        $sql,
        array_values($values)
    );
}

public function insertGetId(array $values): int
{
    $this->insert($values);

    return (int) $this->connection->lastInsertId();
}

public function update(array $values): bool
{
    $sql = $this->grammar->compileUpdate(
        $this,
        $values
    );

    return $this->connection->statement(
        $sql,
        [
            ...array_values($values),
            ...$this->bindings(),
        ]
    );
}

public function delete(): bool
{
    return $this->connection->statement(
        $this->grammar->compileDelete($this),
        $this->bindings()
    );
}

public function truncate(): bool
{
    return $this->connection->statement(
        $this->grammar->compileTruncate($this->table)
    );
}

}