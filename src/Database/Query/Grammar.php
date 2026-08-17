<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

use InvalidArgumentException;

abstract class Grammar
{
    protected string $tablePrefix = '';

    public function setTablePrefix(string $prefix): void
    {
        $this->tablePrefix = $prefix;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function wrap(string|Expression $value): string
    {
        if ($value instanceof Expression) {
            return $value->value();
        }

        if ($value === '*') {
            return '*';
        }

        if (str_contains(strtolower($value), ' as ')) {
            return $this->wrapAliasedValue($value);
        }

        if (str_contains($value, '.')) {
            return implode(
                '.',
                array_map(
                    fn (string $segment): string => $this->wrapSegment($segment),
                    explode('.', $value)
                )
            );
        }

        return $this->wrapSegment($value);
    }

    public function wrapTable(string|Expression $table): string
    {
        if ($table instanceof Expression) {
            return $table->value();
        }

        if (str_contains(strtolower($table), ' as ')) {
            return $this->wrapAliasedTable($table);
        }

        return $this->wrap($this->tablePrefix . $table);
    }

    /**
     * @param array<int, string|Expression> $columns
     */
    public function columnize(array $columns): string
    {
        if ($columns === []) {
            return '*';
        }

        return implode(
            ', ',
            array_map(fn (string|Expression $column): string => $this->wrap($column), $columns)
        );
    }

    /**
     * @param array<int, mixed> $values
     */
    public function parameterize(array $values): string
    {
        return implode(
            ', ',
            array_map(fn (mixed $value): string => $this->parameter($value), $values)
        );
    }

    public function parameter(mixed $value): string
    {
        return $value instanceof Expression ? $value->value() : '?';
    }

    public function quoteString(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * @param array<int|string, mixed> $values
     */
    public function prepareBindings(array $values): array
    {
        return array_values(array_filter(
            $values,
            static fn (mixed $value): bool => !($value instanceof Expression)
        ));
    }

    public function isExpression(mixed $value): bool
    {
        return $value instanceof Expression;
    }

    protected function wrapAliasedValue(string $value): string
    {
        $segments = preg_split('/\s+as\s+/i', $value);

        if ($segments === false || count($segments) !== 2) {
            throw new InvalidArgumentException("Invalid aliased value [$value].");
        }

        return $this->wrap($segments[0]) . ' AS ' . $this->wrapSegment($segments[1]);
    }

    protected function wrapAliasedTable(string $table): string
    {
        $segments = preg_split('/\s+as\s+/i', $table);

        if ($segments === false || count($segments) !== 2) {
            throw new InvalidArgumentException("Invalid aliased table [$table].");
        }

        return $this->wrapTable($segments[0]) . ' AS ' . $this->wrapSegment($this->tablePrefix . $segments[1]);
    }

    abstract protected function wrapSegment(string $segment): string;
}