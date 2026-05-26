<?php
namespace Core\Auth;

interface SessionDriver {
    public static function schema(): array;
    public function read(string $id): array;
    public function write(string $id, array $data): bool;
    public function destroy(string $id): bool;
    public function exists(string $id): bool;
    public function gc(int $maxLifetime): void;
}
