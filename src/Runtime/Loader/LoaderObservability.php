<?php
namespace Core\Runtime\Loader;

class LoaderObservability
{
    private array $events = [];
    private float $bootStart = 0;
    private float $bootEnd = 0;

    public function recordEvent(string $type, string $module, array $data = []): void
    {
        $this->events[] = [
            'type' => $type,
            'module' => $module,
            'time' => microtime(true),
            'data' => $data,
        ];
    }

    public function markBootStart(): void
    {
        $this->bootStart = microtime(true);
    }

    public function markBootEnd(): void
    {
        $this->bootEnd = microtime(true);
    }

    public function metrics(RuntimeLoader $loader): array
    {
        $stats = $loader->stats();
        return [
            'boot_duration_ms' => round(($this->bootEnd - $this->bootStart) * 1000, 3),
            'modules_total' => $stats['registry']['total'],
            'modules_enabled' => $stats['registry']['enabled'],
            'modules_disabled' => $stats['registry']['disabled'],
            'preload_count' => $stats['preloader']['count'],
            'preload_errors' => $stats['preloader']['errors'],
            'preload_duration_ms' => $stats['preloader']['duration_ms'],
            'lazy_class_entries' => $stats['lazy']['class_entries'],
            'lazy_file_entries' => $stats['lazy']['file_entries'],
            'lazy_hits' => $stats['lazy']['hits'],
            'lazy_misses' => $stats['lazy']['misses'],
            'booted' => $stats['booted'],
            'events' => count($this->events),
        ];
    }

    public function prometheusText(RuntimeLoader $loader): string
    {
        $m = $this->metrics($loader);
        $lines = [];
        $lines[] = '# HELP nexph_loader_boot_duration_ms Loader boot duration.';
        $lines[] = '# TYPE nexph_loader_boot_duration_ms gauge';
        $lines[] = 'nexph_loader_boot_duration_ms ' . $m['boot_duration_ms'];
        $lines[] = '# HELP nexph_loader_modules_total Total modules registered.';
        $lines[] = '# TYPE nexph_loader_modules_total gauge';
        $lines[] = 'nexph_loader_modules_total ' . $m['modules_total'];
        $lines[] = '# HELP nexph_loader_preload_count Preloaded files.';
        $lines[] = '# TYPE nexph_loader_preload_count gauge';
        $lines[] = 'nexph_loader_preload_count ' . $m['preload_count'];
        $lines[] = '# HELP nexph_loader_preload_errors Preload errors.';
        $lines[] = '# TYPE nexph_loader_preload_errors counter';
        $lines[] = 'nexph_loader_preload_errors ' . $m['preload_errors'];
        $lines[] = '# HELP nexph_loader_lazy_hits Lazy resolver hits.';
        $lines[] = '# TYPE nexph_loader_lazy_hits counter';
        $lines[] = 'nexph_loader_lazy_hits ' . $m['lazy_hits'];
        $lines[] = '# HELP nexph_loader_lazy_misses Lazy resolver misses.';
        $lines[] = '# TYPE nexph_loader_lazy_misses counter';
        $lines[] = 'nexph_loader_lazy_misses ' . $m['lazy_misses'];
        return implode("\n", $lines) . "\n";
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function diagnostics(RuntimeLoader $loader): array
    {
        return [
            'metrics' => $this->metrics($loader),
            'preload_errors' => $loader->getPreloader()->getErrors(),
            'events' => $this->events,
        ];
    }
}
