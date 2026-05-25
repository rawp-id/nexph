<?php
/**
 * Example: Capability Detection
 * 
 * Demonstrates runtime capability detection and fallback behavior.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Runtime;

echo "=== Runtime Capability Detection ===\n\n";

// Initialize runtime
Runtime::init();

// Check availability
echo "Runtime Available: " . (Runtime::available() ? 'YES' : 'NO') . "\n\n";

// Get capabilities
$caps = Runtime::capabilities();

echo "Capabilities:\n";
echo "  - Fibers:  " . ($caps['fibers'] ? 'YES' : 'NO') . " (PHP 8.1+ coroutines)\n";
echo "  - CLI:     " . ($caps['cli'] ? 'YES' : 'NO') . " (Command line mode)\n";
echo "  - PCNTL:   " . ($caps['pcntl'] ? 'YES' : 'NO') . " (Process control)\n";
echo "  - Sockets: " . ($caps['sockets'] ? 'YES' : 'NO') . " (Socket I/O)\n";
echo "  - POSIX:   " . ($caps['posix'] ? 'YES' : 'NO') . " (POSIX functions)\n";
echo "  - Redis:   " . ($caps['redis'] ? 'YES' : 'NO') . " (Redis extension)\n";

echo "\n=== Feature Availability ===\n\n";

// Check specific features
if (Runtime::available()) {
    echo "✓ Coroutines (Runtime::spawn)\n";
    echo "✓ Event Loop (Runtime::run)\n";
    echo "✓ Cooperative Sleep (Runtime::sleep)\n";
    echo "✓ Channels (message passing)\n";
    echo "✓ Timers (one-shot and repeating)\n";
    
    if ($caps['pcntl']) {
        echo "✓ Worker Processes (with signals)\n";
        echo "✓ Process Forking\n";
    } else {
        echo "✗ Worker Processes (requires pcntl)\n";
        echo "✗ Process Forking (requires pcntl)\n";
    }
    
    if ($caps['sockets']) {
        echo "✓ Socket I/O (async networking)\n";
    } else {
        echo "✗ Socket I/O (requires sockets extension)\n";
    }
} else {
    echo "✗ Runtime features unavailable\n";
    echo "  Reason: ";
    if (!$caps['fibers']) {
        echo "PHP 8.1+ required for Fiber support\n";
    } else if (!$caps['cli']) {
        echo "CLI mode required (not available in FPM/Apache)\n";
    }
}

echo "\n=== Fallback Behavior ===\n\n";

// Demonstrate fallback
echo "Testing Runtime::spawn()...\n";

Runtime::spawn(function() {
    echo "  - Task executed ";
    if (Runtime::available()) {
        echo "(async)\n";
    } else {
        echo "(sync fallback)\n";
    }
});

if (Runtime::available()) {
    Runtime::run();
} else {
    echo "  - No event loop needed (sync execution)\n";
}

echo "\nTesting Runtime::sleep()...\n";
$start = microtime(true);
Runtime::sleep(0.1);
$elapsed = round((microtime(true) - $start) * 1000, 1);
echo "  - Slept for {$elapsed}ms ";
if (Runtime::available()) {
    echo "(cooperative)\n";
} else {
    echo "(blocking)\n";
}

echo "\n=== Deployment Recommendations ===\n\n";

if (Runtime::available()) {
    echo "✓ Full runtime available\n";
    echo "  Recommended: Use async features for background tasks\n";
    echo "  Example: php worker.php --daemon\n";
} else {
    echo "✗ Runtime unavailable\n";
    if (!$caps['cli']) {
        echo "  Current: Web server mode (FPM/Apache)\n";
        echo "  Recommended: Use stateless request/response\n";
        echo "  For background tasks: Set up CLI worker separately\n";
    } else {
        echo "  Current: PHP < 8.1\n";
        echo "  Recommended: Upgrade to PHP 8.1+ for async features\n";
    }
}

echo "\nDone!\n";
