<?php

namespace Nexph\DevServer;

class HotReloadServer
{
    private int $port;
    private mixed $socket = null;
    private array $clients = [];

    public function __construct(int $port = 35729)
    {
        $this->port = $port;
    }

    public function start(): void
    {
        $this->socket = stream_socket_server(
            'tcp://0.0.0.0:' . $this->port,
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );

        if (!$this->socket) {
            throw new \RuntimeException("HMR socket failed: {$errstr} ({$errno})");
        }

        stream_set_blocking($this->socket, false);
        echo "[HMR] WebSocket server on ws://localhost:{$this->port}\n";
    }

    public function tick(): void
    {
        if (!$this->socket) return;

        // accept new connections
        $client = @stream_socket_accept($this->socket, 0);
        if ($client) {
            stream_set_blocking($client, false);
            $this->handshake($client);
        }

        // read/drop client frames to detect disconnects
        foreach ($this->clients as $i => $c) {
            $data = @fread($c, 512);
            if ($data === false || feof($c)) {
                fclose($c);
                unset($this->clients[$i]);
            }
        }
    }

    public function broadcast(string $type, array $payload = []): void
    {
        $msg   = json_encode(['type' => $type, 'payload' => $payload]);
        $frame = $this->encodeFrame($msg);
        foreach ($this->clients as $i => $c) {
            if (@fwrite($c, $frame) === false) {
                fclose($c);
                unset($this->clients[$i]);
            }
        }
    }

    public function stop(): void
    {
        foreach ($this->clients as $c) {
            @fclose($c);
        }
        if ($this->socket) {
            fclose($this->socket);
        }
    }

    private function handshake(mixed $client): void
    {
        $request = fread($client, 4096);
        if (!preg_match('/Sec-WebSocket-Key:\s*(.+)\r\n/i', $request, $m)) {
            fclose($client);
            return;
        }
        $key      = trim($m[1]);
        $accept   = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $response = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
        fwrite($client, $response);
        $this->clients[] = $client;
    }

    private function encodeFrame(string $payload): string
    {
        $len    = strlen($payload);
        $header = chr(0x81); // FIN + text opcode
        if ($len < 126) {
            $header .= chr($len);
        } elseif ($len < 65536) {
            $header .= chr(126) . pack('n', $len);
        } else {
            $header .= chr(127) . pack('J', $len);
        }
        return $header . $payload;
    }
}
