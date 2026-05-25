<?php
/**
 * Example: Simple HTTP Server
 * 
 * Demonstrates socket-based HTTP server using runtime.
 * Requires sockets extension.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Socket;

if (!Runtime::available()) {
    die("Runtime requires CLI mode and PHP 8.1+ Fibers\n");
}

$caps = Runtime::capabilities();
if (!$caps['sockets']) {
    die("This example requires sockets extension\n");
}

echo "=== Simple HTTP Server ===\n";

$server = Socket::tcp();
if (!$server) {
    die("Failed to create socket\n");
}

$server->setNonBlocking(true);

if (!$server->bind('127.0.0.1', 8080)) {
    die("Failed to bind to 127.0.0.1:8080\n");
}

if (!$server->listen()) {
    die("Failed to listen\n");
}

echo "Server listening on http://127.0.0.1:8080\n";
echo "Press Ctrl+C to stop\n\n";

$requestCount = 0;

Runtime::spawn(function() use ($server, &$requestCount) {
    while (true) {
        $client = $server->accept();
        
        if ($client) {
            $requestCount++;
            $reqNum = $requestCount;
            
            // Spawn coroutine for each connection
            Runtime::spawn(function() use ($client, $reqNum) {
                echo "[Request #{$reqNum}] Accepted connection\n";
                
                $data = $client->read(8192);
                if ($data === false) {
                    $client->close();
                    return;
                }
                
                // Parse request line
                $lines = explode("\r\n", $data);
                $requestLine = $lines[0] ?? '';
                echo "[Request #{$reqNum}] {$requestLine}\n";
                
                // Simulate processing
                Runtime::sleep(0.1);
                
                // Send response
                $body = json_encode([
                    'message' => 'Hello from Nexph Runtime!',
                    'request' => $reqNum,
                    'time' => date('Y-m-d H:i:s'),
                ], JSON_PRETTY_PRINT);
                
                $response = "HTTP/1.1 200 OK\r\n";
                $response .= "Content-Type: application/json\r\n";
                $response .= "Content-Length: " . strlen($body) . "\r\n";
                $response .= "Connection: close\r\n";
                $response .= "\r\n";
                $response .= $body;
                
                $client->write($response);
                $client->close();
                
                echo "[Request #{$reqNum}] Response sent\n";
            });
        }
        
        Runtime::yield();
    }
});

Runtime::run();
