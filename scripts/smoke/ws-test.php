#!/usr/bin/env php
<?php
// Usage: php scripts/ws-test.php [--host=localhost] [--port=8081] [--path=/ws] [--clients=2] [--message=test] [--timeout=5]

$options = getopt('', ['host::', 'port::', 'path::', 'clients::', 'message::', 'timeout::', 'help']);
if (isset($options['help'])) {
    echo "Usage: php scripts/ws-test.php [--host=localhost] [--port=8081] [--path=/ws] [--clients=2] [--message=test] [--timeout=5]\n";
    exit(0);
}

$host = (string) ($options['host'] ?? 'localhost');
$port = (int) ($options['port'] ?? 8081);
$path = normalizePath((string) ($options['path'] ?? '/ws'));
$clients = max(1, (int) ($options['clients'] ?? 2));
$message = (string) ($options['message'] ?? 'test');
$timeout = max(1, (int) ($options['timeout'] ?? 5));
$passed = 0;
$failed = 0;

out("NexPH WebSocket Test Suite");
out("Target: ws://{$host}:{$port}{$path}");
out("Clients: {$clients}");

$sockets = [];
for ($i = 0; $i < $clients; $i++) {
    $socket = wsConnect($host, $port, $path, $timeout);
    if (!$socket) {
        fail("client {$i} connect");
        cleanup($sockets);
        exit(1);
    }
    stream_set_blocking($socket, false);
    $sockets[] = $socket;
    pass("client {$i} connected");
}

$hello = readJson($sockets[0], $timeout);
if (($hello['type'] ?? null) === 'hello') {
    pass('hello frame');
} else {
    fail('hello frame');
}

if (count($sockets) > 1) {
    drain($sockets[1], 0.25);
}
drain($sockets[0], 0.25);

wsWrite($sockets[0], $message);
$senderMessage = waitForType($sockets[0], 'message', $timeout);
if (($senderMessage['data'] ?? null) === $message) {
    pass('sender echo');
} else {
    fail('sender echo');
}

if (count($sockets) > 1) {
    $broadcast = waitForType($sockets[1], 'message', $timeout);
    if (($broadcast['data'] ?? null) === $message) {
        pass('broadcast');
    } else {
        fail('broadcast');
    }
}

wsPing($sockets[0], 'ping');
$pong = waitForOpcode($sockets[0], 0x0A, $timeout);
if (($pong['payload'] ?? null) === 'ping') {
    pass('ping/pong');
} else {
    fail('ping/pong');
}

wsClose($sockets[0], 1000, 'done');
$close = waitForOpcode($sockets[0], 0x08, $timeout);
if (($close['code'] ?? 0) === 1000) {
    pass('normal close');
} else {
    fail('normal close');
}

cleanup($sockets);
out("Summary: {$passed} passed, {$failed} failed");
exit($failed > 0 ? 1 : 0);

function wsConnect(string $host, int $port, string $path, int $timeout) {
    $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $timeout);
    if (!$socket) {
        return null;
    }

    $key = base64_encode(random_bytes(16));
    $request = "GET {$path} HTTP/1.1\r\n"
        . "Host: {$host}:{$port}\r\n"
        . "Upgrade: websocket\r\n"
        . "Connection: Upgrade\r\n"
        . "Sec-WebSocket-Key: {$key}\r\n"
        . "Sec-WebSocket-Version: 13\r\n\r\n";
    fwrite($socket, $request);

    $response = '';
    $deadline = microtime(true) + $timeout;
    while (microtime(true) < $deadline && !str_contains($response, "\r\n\r\n")) {
        $chunk = fread($socket, 4096);
        if ($chunk !== false && $chunk !== '') {
            $response .= $chunk;
        }
    }

    if (!str_starts_with($response, 'HTTP/1.1 101')) {
        fclose($socket);
        return null;
    }

    return $socket;
}

function wsWrite($socket, string $payload, int $opcode = 0x01): void {
    fwrite($socket, encodeFrame($payload, $opcode, true));
}

function wsPing($socket, string $payload = ''): void {
    wsWrite($socket, $payload, 0x09);
}

function wsClose($socket, int $code = 1000, string $reason = ''): void {
    wsWrite($socket, pack('n', $code) . $reason, 0x08);
}

function readJson($socket, int $timeout): ?array {
    $frame = waitForOpcode($socket, 0x01, $timeout);
    if (!$frame) {
        return null;
    }
    $json = json_decode($frame['payload'], true);
    return is_array($json) ? $json : null;
}

function waitForType($socket, string $type, int $timeout): ?array {
    $deadline = microtime(true) + $timeout;
    while (microtime(true) < $deadline) {
        $frame = waitForOpcode($socket, 0x01, 1);
        if (!$frame) {
            continue;
        }
        $json = json_decode($frame['payload'], true);
        if (is_array($json) && ($json['type'] ?? null) === $type) {
            return $json;
        }
    }
    return null;
}

function waitForOpcode($socket, int $opcode, int $timeout): ?array {
    $buffer = '';
    $deadline = microtime(true) + $timeout;
    while (microtime(true) < $deadline) {
        $read = [$socket];
        $write = null;
        $except = null;
        if (@stream_select($read, $write, $except, 0, 100000) < 1) {
            continue;
        }
        $chunk = fread($socket, 65536);
        if ($chunk === false || $chunk === '') {
            continue;
        }
        $buffer .= $chunk;
        $frame = decodeFrame($buffer);
        if (!$frame) {
            continue;
        }
        if ($frame['opcode'] === 0x09) {
            wsWrite($socket, $frame['payload'], 0x0A);
            $buffer = substr($buffer, $frame['length']);
            continue;
        }
        if ($frame['opcode'] === 0x08) {
            $payload = $frame['payload'];
            $frame['code'] = strlen($payload) >= 2 ? unpack('n', substr($payload, 0, 2))[1] : 1005;
        }
        if ($frame['opcode'] === $opcode) {
            return $frame;
        }
        $buffer = substr($buffer, $frame['length']);
    }
    return null;
}

function encodeFrame(string $payload, int $opcode, bool $mask): string {
    $len = strlen($payload);
    $frame = chr(0x80 | ($opcode & 0x0F));
    if ($len < 126) {
        $frame .= chr(($mask ? 0x80 : 0) | $len);
    } elseif ($len < 65536) {
        $frame .= chr(($mask ? 0x80 : 0) | 126) . pack('n', $len);
    } else {
        $frame .= chr(($mask ? 0x80 : 0) | 127) . pack('N2', intdiv($len, 4294967296), $len % 4294967296);
    }
    if (!$mask) {
        return $frame . $payload;
    }

    $maskKey = random_bytes(4);
    $masked = $payload;
    for ($i = 0; $i < $len; $i++) {
        $masked[$i] = $masked[$i] ^ $maskKey[$i % 4];
    }
    return $frame . $maskKey . $masked;
}

function decodeFrame(string $data): ?array {
    $size = strlen($data);
    if ($size < 2) return null;
    $byte1 = ord($data[0]);
    $byte2 = ord($data[1]);
    $len = $byte2 & 0x7F;
    $offset = 2;
    if ($len === 126) {
        if ($size < 4) return null;
        $len = unpack('n', substr($data, 2, 2))[1];
        $offset = 4;
    } elseif ($len === 127) {
        if ($size < 10) return null;
        $parts = unpack('Nhi/Nlo', substr($data, 2, 8));
        $len = ($parts['hi'] * 4294967296) + $parts['lo'];
        $offset = 10;
    }
    $masked = ($byte2 & 0x80) !== 0;
    $mask = '';
    if ($masked) {
        if ($size < $offset + 4) return null;
        $mask = substr($data, $offset, 4);
        $offset += 4;
    }
    if ($size < $offset + $len) return null;
    $payload = substr($data, $offset, $len);
    if ($masked) {
        for ($i = 0; $i < $len; $i++) {
            $payload[$i] = $payload[$i] ^ $mask[$i % 4];
        }
    }
    return [
        'opcode' => $byte1 & 0x0F,
        'payload' => $payload,
        'length' => $offset + $len,
    ];
}

function drain($socket, float $seconds): void {
    $deadline = microtime(true) + $seconds;
    while (microtime(true) < $deadline) {
        $read = [$socket];
        $write = null;
        $except = null;
        if (@stream_select($read, $write, $except, 0, 10000) > 0) {
            fread($socket, 65536);
        }
    }
}

function cleanup(array $sockets): void {
    foreach ($sockets as $socket) {
        if (is_resource($socket)) {
            @fclose($socket);
        }
    }
}

function normalizePath(string $path): string {
    $path = '/' . ltrim(trim($path), '/');
    return $path === '/' ? '/ws' : $path;
}

function out(string $message): void {
    echo $message . "\n";
}

function pass(string $message): void {
    global $passed;
    $passed++;
    out("  ✓ {$message}");
}

function fail(string $message): void {
    global $failed;
    $failed++;
    out("  ✗ {$message}");
}
