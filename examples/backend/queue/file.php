<?php
/**
 * File driver example with persistence.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;

// Create queue with file driver
$queuePath = sys_get_temp_dir() . '/nexph-queue-example';
$queue = QueueFactory::createWithDriver('file', [
    'file_path' => $queuePath,
    'workers' => 2,
]);

// Register handler
$queue->register('backup', function($payload, $job) {
    echo "Backing up {$payload['file']}...\n";
    sleep(1);
    echo "Backup completed: {$payload['file']}\n";
    return ['backed_up' => $payload['file']];
});

// Push jobs
echo "Pushing backup jobs to file queue...\n";
echo "Queue path: {$queuePath}\n\n";

$files = ['database.sql', 'uploads.tar.gz', 'config.json', 'logs.zip'];
foreach ($files as $file) {
    $queue->push('backup', ['file' => $file]);
}

// Show queue files
echo "Queue files created:\n";
$jobFiles = glob($queuePath . '/jobs/*.json');
echo "- " . count($jobFiles) . " job files\n\n";

echo "Starting workers...\n";
$queue->work();

echo "\nAll backups completed!\n";

// Cleanup
echo "\nCleaning up queue files...\n";
array_map('unlink', glob($queuePath . '/jobs/*.json'));
array_map('unlink', glob($queuePath . '/dead-letters/*.json'));
rmdir($queuePath . '/jobs');
rmdir($queuePath . '/dead-letters');
rmdir($queuePath);
echo "Cleanup done!\n";
