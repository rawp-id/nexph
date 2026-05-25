<?php
/**
 * HTTP observability endpoint.
 * Exposes runtime metrics and health status via HTTP.
 */

require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Observability\RuntimeMetrics;
use Core\Runtime\Observability\HealthMonitor;
use Core\Runtime\Observability\Dashboard;
use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Observability\RuntimeState;

header('Content-Type: application/json');

$endpoint = $_GET['endpoint'] ?? 'status';

try {
    $metrics = new RuntimeMetrics();
    $health = new HealthMonitor();
    $dashboard = new Dashboard($metrics, $health);
    
    switch ($endpoint) {
        case 'status':
            $response = [
                'status' => 'ok',
                'timestamp' => time(),
                'health' => $health->check(),
            ];
            break;
            
        case 'metrics':
            $response = [
                'status' => 'ok',
                'timestamp' => time(),
                'metrics' => $metrics->toArray(),
            ];
            break;
            
        case 'dashboard':
            $response = [
                'status' => 'ok',
                'timestamp' => time(),
                'dashboard' => $dashboard->getSnapshot(),
                'runtime_state' => RuntimeState::snapshot(),
            ];
            break;

        case 'state':
            $response = [
                'status' => 'ok',
                'timestamp' => time(),
                'runtime_state' => RuntimeState::snapshot(),
            ];
            break;
            
        case 'health':
            $healthCheck = $health->check();
            http_response_code($healthCheck['state'] === 'healthy' ? 200 : 503);
            $response = [
                'status' => $healthCheck['state'],
                'timestamp' => time(),
                'checks' => $healthCheck['checks'],
            ];
            break;
            
        case 'queue':
            $driver = $_GET['driver'] ?? getenv('QUEUE_DRIVER') ?: 'file';
            $queue = QueueFactory::create($driver);
            $queueStatus = $queue->status();
            
            $response = [
                'status' => 'ok',
                'timestamp' => time(),
                'queue' => $queueStatus,
            ];
            break;
            
        default:
            http_response_code(404);
            $response = [
                'status' => 'error',
                'message' => 'Unknown endpoint',
                'available' => ['status', 'metrics', 'dashboard', 'state', 'health', 'queue'],
            ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], JSON_PRETTY_PRINT);
}
