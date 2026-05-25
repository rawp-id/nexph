<?php

namespace Nexph\DevServer;

use Nexph\Builder\BuildPipeline;

class HotReloadLive
{
    private BuildPipeline $pipeline;
    private HotReloadServer $hmr;
    private Watcher $watcher;
    private string $entryFile;
    private string $outputDir;
    private array $config;

    public function __construct(string $entryFile, string $outputDir, array $config = [])
    {
        $this->pipeline   = new BuildPipeline();
        $this->hmr        = new HotReloadServer($config['hmrPort'] ?? 35729);
        $this->watcher    = new Watcher();
        $this->entryFile  = $entryFile;
        $this->outputDir  = $outputDir;
        $this->config     = array_merge(['minify' => false], $config);
    }

    public function run(array $watchPaths): void
    {
        $this->hmr->start();
        $this->watcher->seed($watchPaths);

        echo "Watching for changes (Ctrl+C to stop)...\n\n";

        while (true) {
            $this->hmr->tick();
            $changed = $this->watcher->check($watchPaths);

            if (!empty($changed)) {
                foreach ($changed as $file) {
                    echo '[changed] ' . basename($file) . "\n";
                }
                $this->rebuild();
            }

            usleep(300 * 1000);
        }
    }

    private function rebuild(): void
    {
        $start = microtime(true);
        try {
            $this->pipeline->run($this->entryFile, array_merge(
                $this->config,
                ['output' => $this->outputDir]
            ));
            $elapsed = round((microtime(true) - $start) * 1000);
            echo '✓ Rebuilt in ' . $elapsed . "ms\n";
            $this->hmr->broadcast('reload', ['time' => $elapsed]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            echo '✗ Build error: ' . $msg . "\n";
            $this->hmr->broadcast('error', ['message' => $msg]);
        }
    }
}
