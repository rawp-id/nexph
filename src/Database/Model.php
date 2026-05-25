<?php

namespace Core\Database;

/**
 * Ultra-thin optional convenience wrapper over QueryBuilder.
 * 
 * NOT used by generated systems (ApiGenerator, UiGenerator, etc).
 * Use RawEngine for generated code.
 * 
 * This class exists ONLY for developer convenience in custom business logic.
 * All transformations delegated to FieldControl.
 * No state, no lifecycle hooks, no magic.
 */
class Model
{
    private string $table;
    private array $meta;

    private function __construct(string $table)
    {
        $this->table = $table;
        $this->meta = Metadata::load($table);
    }

    public static function table(string $table): self
    {
        return new self($table);
    }

    public function query(): QueryBuilder
    {
        return new QueryBuilder($this->table);
    }

    public function find(mixed $id): ?array
    {
        $result = $this->query()->where('id', '=', $id)->first();
        return $result ? FieldControl::applyCasts(FieldControl::applyHidden($result, $this->meta), $this->meta) : null;
    }

    public function all(): array
    {
        $results = $this->query()->get();
        return array_map(fn($r) => FieldControl::applyCasts(FieldControl::applyHidden($r, $this->meta), $this->meta), $results);
    }

    public function create(array $data): ?array
    {
        $data = FieldControl::applyFillable($data, $this->meta);
        $this->query()->insert($data);
        return $this->find(DB::lastInsertId());
    }

    public function update(mixed $id, array $data): ?array
    {
        $data = FieldControl::applyFillable($data, $this->meta);
        $this->query()->where('id', '=', $id)->update($data);
        return $this->find($id);
    }

    public function delete(mixed $id): bool
    {
        return $this->query()->where('id', '=', $id)->delete();
    }
}
