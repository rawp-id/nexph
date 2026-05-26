<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Observability\RuntimeState;

class RuntimeWorkersCommand extends Command {
    protected string $name = 'runtime:workers';
    protected string $description = 'Display runtime worker lifecycle status';

    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $state = RuntimeState::snapshot($parsed['options']['driver'] ?? null);
        if (isset($parsed['options']['json'])) {
            echo json_encode(['workers' => $state['queue']['workers'], 'queue' => $state['queue'], 'runtime' => $state['runtime']], JSON_PRETTY_PRINT) . "\n";
            return 0;
        }
        echo sprintf("workers=%d active_fibers=%d active_timers=%d mode=%s queue=%d running=%s\n", $state['queue']['workers'], $state['gauges']['active_fibers'], $state['gauges']['active_timers'], $state['runtime']['mode'], $state['queue']['depth'], $state['queue']['running'] ? 'yes' : 'no');
        return 0;
    }
}
