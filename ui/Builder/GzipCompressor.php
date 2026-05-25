<?php

namespace Nexph\Builder;

class GzipCompressor
{
    /**
     * Compress content with gzip and return binary string.
     * Requires zlib extension (bundled with PHP by default).
     */
    public function compress(string $content, int $level = 9): string
    {
        if (!function_exists('gzencode')) {
            throw new \RuntimeException('zlib extension is required for gzip compression');
        }

        $compressed = gzencode($content, $level);

        if ($compressed === false) {
            throw new \RuntimeException('gzip compression failed');
        }

        return $compressed;
    }

    /**
     * Write a .gz sidecar file next to the original.
     * Returns the path of the written .gz file.
     */
    public function writeGz(string $filePath, string $content, int $level = 9): string
    {
        $gz   = $this->compress($content, $level);
        $dest = $filePath . '.gz';
        file_put_contents($dest, $gz);
        return $dest;
    }

    public function ratio(string $original, string $compressed): float
    {
        $orig = strlen($original);
        if ($orig === 0) return 0.0;
        return round((1 - strlen($compressed) / $orig) * 100, 1);
    }
}
