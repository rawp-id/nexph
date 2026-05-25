<?php
require_once __DIR__ . '/../core/Support/Config.php';
require_once __DIR__ . '/../core/Auth/Auth.php';
require_once __DIR__ . '/../core/Auth/SessionGuard.php';
require_once __DIR__ . '/../core/Auth/Session.php';
require_once __DIR__ . '/../core/Database/DB.php';

use Core\Support\Config;
use Core\Auth\Auth;
use Core\Auth\SessionGuard;
use Core\Auth\Session;
use Core\Database\DB;

Config::loadEnv(__DIR__ . '/../.env');
Config::load(__DIR__ . '/../config/app.php');
DB::connect(Config::get('db'));

Session::configure([
    'driver' => 'file',
    'lifetime' => 7200,
    'path' => __DIR__ . '/../storage/sessions',
    'cookie_name' => 'nexph_session',
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

echo "=== API & Dashboard Integration Test ===\n\n";

// Start Server
echo "[Server] Starting... ";
$serverLog = __DIR__ . '/../storage/logs/server.log';
@mkdir(dirname($serverLog), 0777, true);
$cmd = "php -S localhost:8001 -t " . __DIR__ . "/../public > $serverLog 2>&1 & echo $!";
$pid = trim(shell_exec($cmd));
sleep(2); // Wait for server to start

if (trim(shell_exec("ps -p $pid -o pid=")) === "") {
    echo "FAIL (Server did not start)\n";
    exit(1);
}
echo "OK (PID: $pid)\n";

$baseUrl = "http://localhost:8001";
$cookieFile = __DIR__ . '/../storage/logs/cookies.txt';
@unlink($cookieFile);

function request($method, $path, $data = null, $headers = [], $useCookie = false) {
    global $baseUrl, $cookieFile;
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($useCookie) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if ($data) {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    return ['status' => $status, 'header' => $header, 'body' => $body];
}

// Test 1: API Heartbeat
echo "[API] GET / ... ";
$res = request('GET', '/');
if ($res['status'] === 200 && strpos($res['body'], 'Nexph') !== false) {
    echo "OK\n";
} else {
    echo "FAIL (Status: {$res['status']})\n";
}

// Test 2: Admin Login Page
echo "[Dashboard] GET /admin/login ... ";
$res = request('GET', '/admin/login', null, [], true);
if ($res['status'] === 200 && strpos($res['body'], 'login') !== false) {
    echo "OK\n";
} else {
    echo "FAIL (Status: {$res['status']})\n";
}

// Test 3: Admin Login Attempt (Invalid)
echo "[Dashboard] POST /admin/login (Invalid) ... ";
$res = request('POST', '/admin/login', ['username' => 'wrong', 'password' => 'wrong'], [], true);
if ($res['status'] === 401) {
    echo "OK\n";
} else {
    echo "FAIL (Status: {$res['status']})\n";
}

// Test 4: Dashboard Access (Unauthorized)
echo "[Dashboard] GET /admin (Unauthorized) ... ";
$res = request('GET', '/admin', null, [], true);
if ($res['status'] === 302 || strpos($res['header'], 'Location: /admin/login') !== false) {
    echo "OK\n";
} else {
    echo "FAIL (Status: {$res['status']})\n";
}

// Cleanup
echo "[Server] Stopping (PID: $pid)... ";
shell_exec("kill $pid");
echo "OK\n";

echo "\n=== Test Finished ===\n";
