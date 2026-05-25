<?php
/**
 * Real-world integration example: E-commerce order processing
 * 
 * Shows how to integrate the runtime queue system into a Nexph application
 * for handling order processing, email notifications, and report generation.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\JobHandler;
use Core\Runtime\Queue\Job;
use Core\Runtime\Runtime;

// Simulated database
$db = [
    'orders' => [],
    'reports' => [],
];

// ============================================================================
// Job Handlers
// ============================================================================

class ProcessOrderHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        global $db;
        $orderId = $payload['order_id'];
        
        echo "[Order] Processing order #{$orderId}\n";
        Runtime::sleep(1.0);
        
        $db['orders'][$orderId]['status'] = 'processing';
        $db['orders'][$orderId]['processed_at'] = time();
        
        return ['order_id' => $orderId, 'status' => 'processed'];
    }
    
    public function after(Job $job, mixed $result): void {
        global $queue;
        $queue->push('send-confirmation', ['order_id' => $result['order_id']]);
        $queue->push('update-inventory', ['order_id' => $result['order_id']]);
    }
}

class SendConfirmationHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        echo "[Email] Sending order confirmation for #{$payload['order_id']}\n";
        Runtime::sleep(0.5);
        return ['sent' => true];
    }
}

class UpdateInventoryHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        global $db;
        echo "[Inventory] Updating inventory for order #{$payload['order_id']}\n";
        Runtime::sleep(0.5);
        $db['orders'][$payload['order_id']]['inventory_updated'] = true;
        return ['updated' => true];
    }
}

class GenerateDailyReportHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        global $db;
        echo "[Report] Generating daily sales report\n";
        Runtime::sleep(2.0);
        
        $report = [
            'date' => date('Y-m-d'),
            'total_orders' => count($db['orders']),
            'total_revenue' => count($db['orders']) * 100,
        ];
        
        $db['reports'][] = $report;
        return $report;
    }
}

// ============================================================================
// Setup
// ============================================================================

for ($i = 1; $i <= 5; $i++) {
    $db['orders'][$i] = ['id' => $i, 'status' => 'pending', 'inventory_updated' => false];
}

$queue = QueueFactory::createWithDriver('memory', [
    'workers' => 3,
    'metrics_interval' => 0,
]);

$queue->register('process-order', ProcessOrderHandler::class);
$queue->register('send-confirmation', SendConfirmationHandler::class);
$queue->register('update-inventory', UpdateInventoryHandler::class);
$queue->register('generate-daily-report', GenerateDailyReportHandler::class);

// ============================================================================
// Run
// ============================================================================

echo "=== E-commerce Order Processing Demo ===\n\n";

echo "1. Queueing orders...\n";
for ($i = 1; $i <= 5; $i++) {
    $queue->push('process-order', ['order_id' => $i]);
}

echo "2. Scheduling daily report (delayed 5s)...\n";
$queue->later(5, 'generate-daily-report', []);

echo "3. Starting workers...\n\n";

Runtime::spawn(function() use ($queue) {
    $queue->work();
});

Runtime::spawn(function() use ($queue) {
    for ($i = 0; $i < 20; $i++) {
        Runtime::sleep(1.0);
        $status = $queue->status();
        
        if ($status['depth'] === 0 && $status['metrics']['completed'] > 0 && $i > 10) {
            Runtime::sleep(2.0);
            $queue->stop();
            Runtime::stop();
            break;
        }
    }
});

Runtime::run();

// ============================================================================
// Results
// ============================================================================

echo "\n4. Results:\n\n";

echo "Orders:\n";
foreach ($db['orders'] as $order) {
    $inv = $order['inventory_updated'] ? 'Yes' : 'No';
    echo "  Order #{$order['id']}: {$order['status']}, Inventory: {$inv}\n";
}

echo "\nReports: " . count($db['reports']) . "\n";
foreach ($db['reports'] as $report) {
    echo "  {$report['date']}: {$report['total_orders']} orders, \${$report['total_revenue']}\n";
}

$metrics = $queue->metrics()->toArray();
echo "\nMetrics:\n";
echo "  Completed: {$metrics['completed']}\n";
echo "  Throughput: {$metrics['throughput']} jobs/sec\n";

echo "\n=== Demo Complete ===\n";
