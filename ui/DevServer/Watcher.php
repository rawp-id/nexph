<?php

namespace Nexph\DevServer;

class Watcher
{
    private array $mtimes = [];

    /**
     * Scan $paths for .php files and return any that changed since last check.
     *
     * @param  string[] $paths  Directories or files to watch
     * @return string[]         Changed file paths
     */
    public function check(array $paths): array
    {
        $changed = [];

        foreach ($this->collectFiles($paths) as $file) {
            $mtime = filemtime($file);

            if (!isset($this->mtimes[$file])) {
                // First scan — record but don't report as changed
                $this->mtimes[$file] = $mtime;
                continue;
            }

            if ($mtime !== $this->mtimes[$file]) {
                $this->mtimes[$file] = $mtime;
                $changed[]           = $file;
            }
        }

        return $changed;
    }

    /**
     * Seed the watcher with current mtimes without reporting changes.
     */
    public function seed(array $paths): void
    {
        foreach ($this->collectFiles($paths) as $file) {
            $this->mtimes[$file] = filemtime($file);
        }
    }

    private function collectFiles(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }
}
