<?php
/**
 * Example: Channel Communication
 * 
 * Demonstrates producer-consumer pattern using channels.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Channel;

if (!Runtime::available()) {
    die("Runtime requires CLI mode and PHP 8.1+ Fibers\n");
}

echo "=== Channel Communication Example ===\n\n";

// Buffered channel
$ch = new Channel(5);

// Producer
Runtime::spawn(function() use ($ch) {
    echo "[Producer] Starting\n";
    for ($i = 1; $i <= 10; $i++) {
        echo "[Producer] Sending: {$i}\n";
        $ch->send($i);
        Runtime::sleep(0.1);
    }
    echo "[Producer] Closing channel\n";
    $ch->close();
});

// Consumer 1
Runtime::spawn(function() use ($ch) {
    echo "[Consumer 1] Starting\n";
    while (($item = $ch->receive()) !== null) {
        echo "[Consumer 1] Received: {$item}\n";
        Runtime::sleep(0.15);
    }
    echo "[Consumer 1] Channel closed\n";
});

// Consumer 2
Runtime::spawn(function() use ($ch) {
    echo "[Consumer 2] Starting\n";
    Runtime::sleep(0.5); // Start later
    while (($item = $ch->receive()) !== null) {
        echo "[Consumer 2] Received: {$item}\n";
        Runtime::sleep(0.2);
    }
    echo "[Consumer 2] Channel closed\n";
});

Runtime::run();

echo "\nDone!\n";
