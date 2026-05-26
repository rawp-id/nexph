<?php

namespace Nexph\Builder;

class AssetHasher
{
    /**
     * Generate a short content-based hash for cache busting.
     */
    public function hash(string $content): string
    {
        return substr(md5($content), 0, 8);
    }

    /**
     * Insert hash into a filename before the extension.
     * e.g. app.js → app.a1b2c3d4.js
     */
    public function hashFilename(string $filename, string $content): string
    {
        $hash = $this->hash($content);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);

        return $ext ? "{$base}.{$hash}.{$ext}" : "{$base}.{$hash}";
    }
}
