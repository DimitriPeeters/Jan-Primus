<?php

declare(strict_types=1);

namespace AEFS\Database\Query;

final class MySqlGrammar extends Grammar
{
    public function compileSelect(QueryBuilder $query): string
    {
        $sql = [];

        $sql[] = $query->distinct()
            ? 'SELECT DISTINCT'
            : 'SELECT';

        $sql[] = $this->columnize($query->columns());

        $sql[] = 'FROM';
        $sql[] = $this->wrapTable($query->table());

        foreach ($query->joins() as $join) {
            $sql[] = $join->compile($this);
        }

        if (!$query->whereClause()->isEmpty()) {
            $sql[] = 'WHERE';
            $sql[] = $query->whereClause()->compile($this);
        }

        if (!$query->groupClause()->isEmpty()) {
            $sql[] = 'GROUP BY';
            $sql[] = $query->groupClause()->compile($this);
        }

        if (!$query->havingClause()->isEmpty()) {
            $sql[] = 'HAVING';
            $sql[] = $query->havingClause()->compile($this);
        }

        if (!$query->orderClause()->isEmpty()) {
            $sql[] = 'ORDER BY';
            $sql[] = $query->orderClause()->compile($this);
        }

        if ($query->limitValue() !== null) {
            $sql[] = 'LIMIT';
            $sql[] = (string) $query->limitValue();
        }

        if ($query->offsetValue() !== null) {
            $sql[] = 'OFFSET';
            $sql[] = (string) $query->offsetValue();
        }

        return implode(' ', $sql);
    }

    public function compileInsert(
        string $table,
        array $values
    ): string {
        $columns = array_keys($values);

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->wrapTable($table),
            $this->columnize($columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
    }

    public function compileInsertMany(
        string $table,
        array $rows
    ): string {
        $columns = array_keys(reset($rows));

        $placeholders = [];

        foreach ($rows as $row) {
            $placeholders[] = '(' .
                implode(', ', array_fill(0, count($row), '?')) .
                ')';
        }

        return sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->wrapTable($table),
            $this->columnize($columns),
            implode(', ', $placeholders)
        );
    }

    public function compileUpdate(
        QueryBuilder $query,
        array $values
    ): string {
        $set = [];

        foreach (array_keys($values) as $column) {
            $set[] = $this->wrap($column) . ' = ?';
        }

        $sql = sprintf(
            'UPDATE %s SET %s',
            $this->wrapTable($query->table()),
            implode(', ', $set)
        );

        if (!$query->whereClause()->isEmpty()) {
            $sql .= ' WHERE ' . $query->whereClause()->compile($this);
        }

        return $sql;
    }

    public function compileDelete(
        QueryBuilder $query
    ): string {
        $sql = sprintf(
            'DELETE FROM %s',
            $this->wrapTable($query->table())
        );

        if (!$query->whereClause()->isEmpty()) {
            $sql .= ' WHERE ' . $query->whereClause()->compile($this);
        }

        return $sql;
    }

    public function compileTruncate(
        string $table
    ): string {
        return sprintf(
            'TRUNCATE TABLE %s',
            $this->wrapTable($table)
        );
    }

    protected function wrapSegment(string $segment): string
    {
        if ($segment === '*') {
            return '*';
        }

        return '`' . str_replace('`', '``', $segment) . '`';
    }
}