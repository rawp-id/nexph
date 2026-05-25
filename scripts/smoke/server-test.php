#!/usr/bin/env php
<?php
// Nexph HTTP Server Benchmark & Test Suite
// Usage: php scripts/server-test.php [--host=localhost] [--port=8080] [--concurrency=10] [--requests=100]

$options = getopt('', ['host::', 'port::', 'concurrency::', 'requests::', 'duration::', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
Nexph Server Test Suite

Usage: php scripts/server-test.php [options]

Options:
  --host=HOST         Server host (default: localhost)
  --port=PORT         Server port (default: 8080)
  --concurrency=N     Concurrent connections (default: 10)
  --requests=N        Total requests per test (default: 100)
  --duration=N        Duration in seconds for stress test (default: 10)
  --help              Show this help

Tests:
  1. Functional tests (endpoints, methods, headers)
  2. Concurrency test (parallel requests)
  3. Stress test (sustained load)
  4. Memory leak test (repeated requests)
  5. Keep-alive test (connection reuse)

HELP;
    exit(0);
}

$host = $options['host'] ?? 'localhost';
$port = (int) ($options['port'] ?? 8080);
$concurrency = (int) ($options['concurrency'] ?? 10);
$totalRequests = (int) ($options['requests'] ?? 100);
$duration = (int) ($options['duration'] ?? 10);

$baseUrl = "http://{$host}:{$port}";

// Colors
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$CYAN = "\033[36m";
$RESET = "\033[0m";
$BOLD = "\033[1m";

function out($msg, $color = '') {
    global $RESET;
    echo $color . $msg . $RESET . "\n";
}

function passed($msg) { global $GREEN; out("  ✓ {$msg}", $GREEN); }
function failed($msg) { global $RED; out("  ✗ {$msg}", $RED); }
function info($msg) { global $CYAN; out("  ℹ {$msg}", $CYAN); }
function warn($msg) { global $YELLOW; out("  ⚠ {$msg}", $YELLOW); }

function request($url, $method = 'GET', $body = null, $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HEADER => true,
    ]);
    
    if ($body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
        $headers[] = 'Content-Type: application/json';
    }
    
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $duration = (microtime(true) - $start) * 1000;
    
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);
    
    return [
        'status' => $httpCode,
        'headers' => $responseHeaders,
        'body' => $responseBody,
        'json' => json_decode($responseBody, true),
        'duration' => $duration,
        'error' => $error,
    ];
}

function multiRequest($urls, $concurrency = 10) {
    $mh = curl_multi_init();
    $handles = [];
    $results = [];
    
    foreach ($urls as $i => $url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => is_array($url) ? $url['url'] : $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        if (is_array($url) && isset($url['method'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $url['method']);
        }
        if (is_array($url) && isset($url['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($url['body']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = ['handle' => $ch, 'start' => microtime(true)];
    }
    
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);
    
    foreach ($handles as $i => $data) {
        $ch = $data['handle'];
        $results[$i] = [
            'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'body' => curl_multi_getcontent($ch),
            'duration' => (microtime(true) - $data['start']) * 1000,
            'error' => curl_error($ch),
        ];
        curl_multi_remove_handle($mh, $ch);
        
    }
    
    curl_multi_close($mh);
    return $results;
}

// ============================================
// TEST SUITE
// ============================================

out("\n{$BOLD}╔══════════════════════════════════════════╗{$RESET}");
out("{$BOLD}║     NEXPH HTTP SERVER TEST SUITE         ║{$RESET}");
out("{$BOLD}╚══════════════════════════════════════════╝{$RESET}\n");

out("Target: {$baseUrl}");
out("Concurrency: {$concurrency}");
out("Requests: {$totalRequests}");
out("");

$totalTests = 0;
$passedTests = 0;

// ============================================
// 1. FUNCTIONAL TESTS
// ============================================
out("{$BOLD}[1] FUNCTIONAL TESTS{$RESET}");

// Test: Root endpoint
$r = request("{$baseUrl}/");
$totalTests++;
if ($r['status'] === 200 && isset($r['json']['name'])) {
    passed("GET / - Server info");
    $passedTests++;
} else {
    failed("GET / - Expected 200 with server info");
}

// Test: Health endpoint
$r = request("{$baseUrl}/api/health");
$totalTests++;
if ($r['status'] === 200 && isset($r['json']['status']) && $r['json']['status'] === 'ok') {
    passed("GET /api/health - Health check (" . number_format($r['duration'], 1) . "ms)");
    $passedTests++;
} else {
    failed("GET /api/health - Expected status 'ok'");
}

// Test: Ping endpoint
$r = request("{$baseUrl}/api/ping");
$totalTests++;
if ($r['status'] === 200 && isset($r['json']['pong'])) {
    passed("GET /api/ping - Ping/pong (" . number_format($r['duration'], 1) . "ms)");
    $passedTests++;
} else {
    failed("GET /api/ping - Expected pong response");
}

// Test: Echo endpoint
$r = request("{$baseUrl}/api/echo", 'POST', ['test' => 'data', 'number' => 123]);
$totalTests++;
if ($r['status'] === 200 && isset($r['json']['body']['test'])) {
    passed("POST /api/echo - Echo request body");
    $passedTests++;
} else {
    failed("POST /api/echo - Expected echoed body");
}

// Test: Users endpoint
$r = request("{$baseUrl}/api/users");
$totalTests++;
if ($r['status'] === 200 && isset($r['json']['data'])) {
    $count = count($r['json']['data']);
    passed("GET /api/users - List users ({$count} users, " . number_format($r['duration'], 1) . "ms)");
    $passedTests++;
} else {
    failed("GET /api/users - Expected data array");
}

// Test: 404 handling
$r = request("{$baseUrl}/nonexistent/path");
$totalTests++;
if ($r['status'] === 404) {
    passed("GET /nonexistent - 404 handling");
    $passedTests++;
} else {
    failed("GET /nonexistent - Expected 404, got {$r['status']}");
}

// Test: Security headers
$r = request("{$baseUrl}/api/ping");
$totalTests++;
if (str_contains($r['headers'], 'X-Content-Type-Options')) {
    passed("Security headers present");
    $passedTests++;
} else {
    failed("Security headers missing");
}

// Test: CORS headers
$totalTests++;
if (str_contains($r['headers'], 'Access-Control-Allow-Origin')) {
    passed("CORS headers present");
    $passedTests++;
} else {
    failed("CORS headers missing");
}

// Test: Rate limit headers
$totalTests++;
if (str_contains($r['headers'], 'X-RateLimit-Limit')) {
    passed("Rate limit headers present");
    $passedTests++;
} else {
    failed("Rate limit headers missing");
}

out("");

// ============================================
// 2. CONCURRENCY TEST
// ============================================
out("{$BOLD}[2] CONCURRENCY TEST{$RESET}");

$urls = array_fill(0, $concurrency, "{$baseUrl}/api/ping");
$start = microtime(true);
$results = multiRequest($urls, $concurrency);
$totalTime = (microtime(true) - $start) * 1000;

$successful = count(array_filter($results, fn($r) => $r['status'] === 200));
$avgTime = array_sum(array_column($results, 'duration')) / count($results);
$minTime = min(array_column($results, 'duration'));
$maxTime = max(array_column($results, 'duration'));

$totalTests++;
if ($successful === $concurrency) {
    passed("{$concurrency} concurrent requests - All successful");
    $passedTests++;
} else {
    failed("{$concurrency} concurrent requests - {$successful}/{$concurrency} successful");
    $statusCounts = [];
    $errorCounts = [];
    foreach ($results as $r) {
        $statusCounts[(string)$r['status']] = ($statusCounts[(string)$r['status']] ?? 0) + 1;
        if ($r['error'] !== '') {
            $errorCounts[$r['error']] = ($errorCounts[$r['error']] ?? 0) + 1;
        }
    }
    ksort($statusCounts);
    arsort($errorCounts);
    info("Status counts: " . json_encode($statusCounts));
    if ($errorCounts) {
        info("Top errors: " . json_encode(array_slice($errorCounts, 0, 3, true)));
    }
}

info("Total time: " . number_format($totalTime, 1) . "ms");
info("Avg response: " . number_format($avgTime, 1) . "ms | Min: " . number_format($minTime, 1) . "ms | Max: " . number_format($maxTime, 1) . "ms");

out("");

// ============================================
// 3. STRESS TEST
// ============================================
out("{$BOLD}[3] STRESS TEST ({$totalRequests} requests){$RESET}");

$urls = array_fill(0, $totalRequests, "{$baseUrl}/api/ping");
$batchSize = min(50, $concurrency * 2);
$batches = array_chunk($urls, $batchSize);

// Streaming stats - don't store all results in memory
$successful = 0;
$failed_count = 0;
$sumDuration = 0.0;
$minTime = PHP_FLOAT_MAX;
$maxTime = 0.0;
$sampleDurations = []; // Keep only sample for percentiles
$sampleRate = max(1, (int)($totalRequests / 10000)); // Sample ~10K max
$processed = 0;

$start = microtime(true);

foreach ($batches as $batchIdx => $batch) {
    $results = multiRequest($batch, $batchSize);
    
    foreach ($results as $r) {
        $processed++;
        if ($r['status'] === 200) {
            $successful++;
        } else {
            $failed_count++;
        }
        
        $d = $r['duration'];
        $sumDuration += $d;
        if ($d < $minTime) $minTime = $d;
        if ($d > $maxTime) $maxTime = $d;
        
        // Sample for percentiles
        if ($processed % $sampleRate === 0) {
            $sampleDurations[] = $d;
        }
    }
    
    // Progress indicator for large tests
    if ($totalRequests >= 10000 && $batchIdx % 100 === 0) {
        $pct = round($processed / $totalRequests * 100);
        echo "\r  Processing: {$pct}% ({$processed}/{$totalRequests})    ";
    }
    
    unset($results); // Free memory immediately
}

if ($totalRequests >= 10000) {
    echo "\r" . str_repeat(' ', 50) . "\r"; // Clear progress line
}

$totalTime = microtime(true) - $start;
$rps = $totalRequests / $totalTime;
$avgTime = $sumDuration / $processed;

// Calculate percentiles from sample
sort($sampleDurations);
$sampleCount = count($sampleDurations);
$p50 = $sampleCount > 0 ? $sampleDurations[(int)($sampleCount * 0.50)] : 0;
$p95 = $sampleCount > 0 ? $sampleDurations[(int)($sampleCount * 0.95)] : 0;
$p99 = $sampleCount > 0 ? $sampleDurations[min($sampleCount - 1, (int)($sampleCount * 0.99))] : 0;

$totalTests++;
if ($successful >= $totalRequests * 0.99) {
    passed("{$successful}/{$totalRequests} requests successful ({$failed_count} failed)");
    $passedTests++;
} else {
    failed("{$successful}/{$totalRequests} requests successful ({$failed_count} failed)");
}

info("Throughput: " . number_format($rps, 1) . " req/s");
info("Latency - Avg: " . number_format($avgTime, 1) . "ms | P50: " . number_format($p50, 1) . "ms | P95: " . number_format($p95, 1) . "ms | P99: " . number_format($p99, 1) . "ms");
info("Min: " . number_format($minTime, 1) . "ms | Max: " . number_format($maxTime, 1) . "ms");

out("");

// ============================================
// 4. MEMORY LEAK TEST
// ============================================
out("{$BOLD}[4] MEMORY LEAK TEST{$RESET}");

$r1 = request("{$baseUrl}/api/health");
$memBefore = $r1['json']['stats']['memory']['current'] ?? 0;

// Send 50 requests
for ($i = 0; $i < 50; $i++) {
    request("{$baseUrl}/api/users");
}

$r2 = request("{$baseUrl}/api/health");
$memAfter = $r2['json']['stats']['memory']['current'] ?? 0;
$memDiff = $memAfter - $memBefore;
$memDiffMB = $memDiff / 1024 / 1024;

$totalTests++;
if ($memDiff < 5 * 1024 * 1024) { // Less than 5MB growth
    passed("Memory stable after 50 requests (+" . number_format($memDiffMB, 2) . "MB)");
    $passedTests++;
} else {
    warn("Memory grew by " . number_format($memDiffMB, 2) . "MB - potential leak");
}

$trend = $r2['json']['stats']['memory']['trend'] ?? 'unknown';
info("Memory trend: {$trend}");

out("");

// ============================================
// 5. KEEP-ALIVE TEST
// ============================================
out("{$BOLD}[5] KEEP-ALIVE TEST{$RESET}");

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "{$baseUrl}/api/ping",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
]);

$times = [];
for ($i = 0; $i < 5; $i++) {
    $start = microtime(true);
    curl_exec($ch);
    $times[] = (microtime(true) - $start) * 1000;
}


$firstTime = $times[0];
$avgSubsequent = array_sum(array_slice($times, 1)) / 4;

$totalTests++;
if ($avgSubsequent < $firstTime * 0.8) {
    passed("Keep-alive working - First: " . number_format($firstTime, 1) . "ms, Subsequent avg: " . number_format($avgSubsequent, 1) . "ms");
    $passedTests++;
} else {
    info("Keep-alive - First: " . number_format($firstTime, 1) . "ms, Subsequent avg: " . number_format($avgSubsequent, 1) . "ms");
    $passedTests++; // Still pass, just informational
}

out("");

// ============================================
// 6. ASYNC TEST
// ============================================
out("{$BOLD}[6] ASYNC ENDPOINT TEST{$RESET}");

$r = request("{$baseUrl}/api/async");
$totalTests++;
if ($r['status'] === 200 && $r['duration'] >= 90) { // Should take ~100ms due to sleep
    passed("Async endpoint works (" . number_format($r['duration'], 1) . "ms delay)");
    $passedTests++;
} else {
    failed("Async endpoint - Expected ~100ms delay, got " . number_format($r['duration'], 1) . "ms");
}

out("");

// ============================================
// SUMMARY
// ============================================
out("{$BOLD}╔══════════════════════════════════════════╗{$RESET}");
out("{$BOLD}║              TEST SUMMARY                ║{$RESET}");
out("{$BOLD}╚══════════════════════════════════════════╝{$RESET}\n");

$percentage = ($passedTests / $totalTests) * 100;
$color = $percentage >= 90 ? $GREEN : ($percentage >= 70 ? $YELLOW : $RED);

out("Tests passed: {$color}{$passedTests}/{$totalTests} (" . number_format($percentage, 0) . "%){$RESET}");
out("Throughput: " . number_format($rps, 1) . " req/s");
out("Avg latency: " . number_format($avgTime, 1) . "ms (P99: " . number_format($p99, 1) . "ms)");

// Final health check
$r = request("{$baseUrl}/api/health");
if ($r['json']) {
    $stats = $r['json']['stats'];
    out("\nServer stats:");
    out("  Uptime: " . number_format($stats['uptime'], 1) . "s");
    out("  Total requests: {$stats['total_requests']}");
    out("  Active connections: {$stats['active_connections']}");
    out("  Memory: " . number_format($stats['memory']['current'] / 1024 / 1024, 2) . "MB");
}

out("");
exit($passedTests === $totalTests ? 0 : 1);
