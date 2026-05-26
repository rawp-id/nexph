<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Observability\HealthMonitor;
use Core\Runtime\Observability\RuntimeState;

class RuntimeHealthCommand extends Command {
    protected string $name = 'runtime:health';
    protected string $description = 'Check runtime health';

    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $json = isset($parsed['options']['json']);
        $health = (new HealthMonitor())->check();
        $state = RuntimeState::snapshot($parsed['options']['driver'] ?? null);
        $out = ['health' => $health, 'runtime' => $state['runtime'], 'queue' => $state['queue'], 'system' => $state['system']];
        if ($json) {
            echo json_encode($out, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo sprintf("%s mode=%s queue=%d dlq=%d mem=%.1fMB\n", $health['state'], $state['runtime']['mode'], $state['queue']['depth'], $state['queue']['dead_letters'], $state['computed']['memory_usage_mb']);
        }
        return $health['state'] === 'unhealthy' ? 2 : 0;
    }
}
