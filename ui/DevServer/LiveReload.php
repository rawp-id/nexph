<?php

namespace Nexph\DevServer;

use Nexph\Builder\BuildPipeline;

class LiveReload
{
    private BuildPipeline $pipeline;
    private string $entryFile;
    private string $outputDir;
    private array $config;

    public function __construct(string $entryFile, string $outputDir, array $config = [])
    {
        $this->pipeline   = new BuildPipeline();
        $this->entryFile  = $entryFile;
        $this->outputDir  = $outputDir;
        $this->config     = array_merge(['minify' => false], $config);
    }

    /**
     * Watch $watchPaths and rebuild whenever a .php file changes.
     * Blocks indefinitely — call from CLI only.
     *
     * @param string[] $watchPaths
     * @param int      $intervalMs  Poll interval in milliseconds
     */
    public function watch(array $watchPaths, int $intervalMs = 500): void
    {
        $watcher = new Watcher();
        $watcher->seed($watchPaths);

        echo "Watching for changes (Ctrl+C to stop)...\n\n";

        while (true) {
            $changed = $watcher->check($watchPaths);

            if (!empty($changed)) {
                foreach ($changed as $file) {
                    echo '[changed] ' . basename($file) . "\n";
                }

                $this->rebuild();
            }

            usleep($intervalMs * 1000);
        }
    }

    private function rebuild(): void
    {
        $start = microtime(true);

        try {
            $result  = $this->pipeline->run($this->entryFile, array_merge(
                $this->config,
                ['output' => $this->outputDir]
            ));
            $elapsed = round((microtime(true) - $start) * 1000);
            echo '✓ Rebuilt in ' . $elapsed . "ms\n";
        } catch (\Throwable $e) {
            echo '✗ Build error: ' . $e->getMessage() . "\n";
        }
    }
}
