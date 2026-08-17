<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

use InvalidArgumentException;

final class OrderClause
{
    /**
     * @var array<int, array{
     *     column:string|Expression,
     *     direction:string
     * }>
     */
    private array $orders = [];

    public function orderBy(
        string|Expression $column,
        string $direction = 'ASC'
    ): self {
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid order direction [%s].',
                    $direction
                )
            );
        }

        $this->orders[] = [
            'column'    => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function latest(
        string|Expression $column = 'created_at'
    ): self {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(
        string|Expression $column = 'created_at'
    ): self {
        return $this->orderBy($column, 'ASC');
    }

    public function reorder(): self
    {
        $this->orders = [];

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->orders === [];
    }

    /**
     * @return array<int, array{
     *     column:string|Expression,
     *     direction:string
     * }>
     */
    public function orders(): array
    {
        return $this->orders;
    }

    public function compile(Grammar $grammar): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $compiled = [];

        foreach ($this->orders as $order) {
            $compiled[] = sprintf(
                '%s %s',
                $grammar->wrap($order['column']),
                $order['direction']
            );
        }

        return implode(', ', $compiled);
    }
}