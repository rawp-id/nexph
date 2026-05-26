<?php

namespace Nexph\Compiler;

/**
 * Phase 5: Compiler cache system.
 * Caches AST, scoped CSS, generated JS, and dep graph per component hash.
 * Skips unchanged compile steps — enables near-instant incremental rebuilds.
 */
class CompilerCache
{
    private string $cacheDir;
    private bool   $enabled;

    public function __construct(string $cacheDir = '', bool $enabled = true)
    {
        $this->cacheDir = $cacheDir ?: sys_get_temp_dir() . '/nexph-cache';
        $this->enabled  = $enabled;
        if ($this->enabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Compute a cache key from source content.
     */
    public function key(string $source): string
    {
        return md5($source);
    }

    /**
     * Return cached compile result or null if miss/stale.
     */
    public function get(string $key): ?array
    {
        if (!$this->enabled) return null;
        $file = $this->path($key);
        if (!file_exists($file)) return null;
        $data = unserialize(file_get_contents($file));
        return is_array($data) ? $data : null;
    }

    /**
     * Store a compile result.
     * Stores: html, css, js, manifest, deps, refs, ast (serialized).
     */
    public function put(string $key, array $result): void
    {
        if (!$this->enabled) return;
        file_put_contents($this->path($key), serialize($result));
    }

    /**
     * Invalidate a single cache entry.
     */
    public function invalidate(string $key): void
    {
        $file = $this->path($key);
        if (file_exists($file)) unlink($file);
    }

    /**
     * Clear all cache entries.
     */
    public function clear(): void
    {
        foreach (glob($this->cacheDir . '/*.nxc') ?: [] as $file) {
            unlink($file);
        }
    }

    /**
     * Return cache stats: count and total size in bytes.
     */
    public function stats(): array
    {
        $files = glob($this->cacheDir . '/*.nxc') ?: [];
        $size  = array_sum(array_map('filesize', $files));
        return ['entries' => count($files), 'bytes' => $size];
    }

    private function path(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.nxc';
    }
}
