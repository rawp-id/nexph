<?php

namespace Core\Database;

class QueryBuilder
{
    private string $table;
    private array $select = ['*'];
    private array $where = [];
    private array $bindings = [];
    private array $joins = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->where[] = ['column' => $column, 'operator' => $operator, 'value' => $value, 'type' => 'AND'];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->where[] = ['column' => $column, 'operator' => $operator, 'value' => $value, 'type' => 'OR'];
        $this->bindings[] = $value;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = ['type' => 'INNER', 'table' => $table, 'first' => $first, 'operator' => $operator, 'second' => $second];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = ['type' => 'LEFT', 'table' => $table, 'first' => $first, 'operator' => $operator, 'second' => $second];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = ['column' => $column, 'direction' => strtoupper($direction)];
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

    public function paginate(int $page, int $perPage): array
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        $data = $this->get();
        $total = $this->count();
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage)
        ];
    }

    public function get(): array
    {
        $sql = $this->toSql();
        return DB::query($sql, $this->bindings);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $result = $this->get();
        return $result[0] ?? null;
    }

    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        $sql = $this->toSql();
        $this->select = $originalSelect;
        $result = DB::query($sql, $this->bindings);
        return (int) ($result[0]['count'] ?? 0);
    }

    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        return DB::execute($sql, array_values($data));
    }

    public function update(array $data): bool
    {
        $set = [];
        $bindings = [];
        foreach ($data as $column => $value) {
            $set[] = "`{$column}` = ?";
            $bindings[] = $value;
        }
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $set);
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->buildWhere();
            $bindings = array_merge($bindings, $this->bindings);
        }
        return DB::execute($sql, $bindings);
    }

    public function delete(): bool
    {
        $sql = "DELETE FROM `{$this->table}`";
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->buildWhere();
        }
        return DB::execute($sql, $this->bindings);
    }

    public function toSql(): string
    {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM `{$this->table}`";
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql .= " {$join['type']} JOIN `{$join['table']}` ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->buildWhere();
        }
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', array_map(fn($o) => "{$o['column']} {$o['direction']}", $this->orderBy));
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    private function buildWhere(): string
    {
        $conditions = [];
        foreach ($this->where as $i => $w) {
            $prefix = $i === 0 ? '' : " {$w['type']} ";
            $conditions[] = $prefix . "`{$w['column']}` {$w['operator']} ?";
        }
        return implode('', $conditions);
    }
}
