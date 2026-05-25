<?php
namespace Core\Server;

class Connection {
    private $socket;
    private string $buffer = '';
    private string $writeBuffer = '';
    private int $id;
    private float $connectedAt;
    private float $lastActivity;
    private float $lastReadAt;
    private float $lastWriteAt;
    private float $lastPingAt = 0.0;
    private float $lastPongAt = 0.0;
    private string $remoteAddr;
    private int $remotePort;
    private bool $keepAlive = true;
    private int $requestCount = 0;
    private bool $webSocket = false;
    private string $webSocketPath = '';
    private bool $sse = false;
    private string $ssePath = '';
    private string $sseChannel = 'global';
    private bool $closing = false;

    public function __construct($socket, int $id) {
        $this->socket = $socket;
        $this->id = $id;
        $this->connectedAt = microtime(true);
        $this->lastActivity = $this->connectedAt;
        $this->lastReadAt = $this->connectedAt;
        $this->lastWriteAt = $this->connectedAt;
        $this->lastPongAt = $this->connectedAt;

        stream_set_blocking($socket, false);

        $name = stream_socket_get_name($socket, true);
        if ($name && strpos($name, ':') !== false) {
            [$this->remoteAddr, $this->remotePort] = explode(':', $name);
            $this->remotePort = (int) $this->remotePort;
        } else {
            $this->remoteAddr = '0.0.0.0';
            $this->remotePort = 0;
        }
    }

    public function getId(): int {
        return $this->id;
    }

    public function getSocket() {
        return $this->socket;
    }

    public function getRemoteAddr(): string {
        return $this->remoteAddr;
    }

    public function getRemotePort(): int {
        return $this->remotePort;
    }

    public function read(): ?string {
        if (!is_resource($this->socket)) {
            return null;
        }

        $data = @fread($this->socket, 65536);

        if ($data === false || $data === '') {
            if (!is_resource($this->socket) || feof($this->socket)) {
                return null; // Connection closed
            }
            return ''; // No data yet
        }

        $this->lastReadAt = microtime(true);
        $this->lastActivity = $this->lastReadAt;
        $this->buffer .= $data;
        return $data;
    }

    public function getBuffer(): string {
        return $this->buffer;
    }

    public function consumeBuffer(int $length): void {
        $this->buffer = substr($this->buffer, $length);
    }

    public function clearBuffer(): void {
        $this->buffer = '';
    }

    public function write(string $data, int $maxBufferSize = 0): int {
        if (!is_resource($this->socket)) {
            return -1;
        }

        if ($maxBufferSize > 0 && strlen($this->writeBuffer) + strlen($data) > $maxBufferSize) {
            return -2;
        }

        $this->writeBuffer .= $data;
        return $this->flush();
    }

    public function flush(): int {
        if ($this->writeBuffer === '') {
            return 0;
        }

        if (!is_resource($this->socket)) {
            $this->writeBuffer = '';
            return -1;
        }

        $written = @fwrite($this->socket, $this->writeBuffer);

        if ($written === false) {
            $this->writeBuffer = '';
            return -1;
        }

        if ($written > 0) {
            $this->writeBuffer = substr($this->writeBuffer, $written);
            $this->lastWriteAt = microtime(true);
            $this->lastActivity = max($this->lastActivity, $this->lastWriteAt);
        }

        return $written;
    }

    public function hasWriteBuffer(): bool {
        return $this->writeBuffer !== '';
    }

    public function getWriteBufferSize(): int {
        return strlen($this->writeBuffer);
    }

    public function close(): void {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
            $this->socket = null;
        }
        // Clear buffers to free memory
        $this->buffer = '';
        $this->writeBuffer = '';
    }

    public function isAlive(): bool {
        return is_resource($this->socket) && !@feof($this->socket);
    }

    public function getLastActivity(): float {
        return $this->lastActivity;
    }

    public function touch(): void {
        $this->lastActivity = microtime(true);
    }

    public function getLastReadAt(): float {
        return $this->lastReadAt;
    }

    public function getLastWriteAt(): float {
        return $this->lastWriteAt;
    }

    public function getLastPingAt(): float {
        return $this->lastPingAt;
    }

    public function markPing(): void {
        $this->lastPingAt = microtime(true);
    }

    public function getLastPongAt(): float {
        return $this->lastPongAt;
    }

    public function markPong(): void {
        $this->lastPongAt = microtime(true);
        $this->lastReadAt = $this->lastPongAt;
        $this->lastActivity = max($this->lastActivity, $this->lastPongAt);
    }

    public function getConnectedAt(): float {
        return $this->connectedAt;
    }

    public function setKeepAlive(bool $keepAlive): void {
        $this->keepAlive = $keepAlive;
    }

    public function isKeepAlive(): bool {
        return $this->keepAlive;
    }

    public function incrementRequestCount(): void {
        $this->requestCount++;
    }

    public function getRequestCount(): int {
        return $this->requestCount;
    }

    public function markWebSocket(string $path): void {
        $now = microtime(true);
        $this->webSocket = true;
        $this->webSocketPath = $path;
        $this->keepAlive = true;
        $this->lastPingAt = 0.0;
        $this->lastPongAt = $now;
        $this->lastReadAt = $now;
        $this->lastWriteAt = $now;
        $this->lastActivity = $now;
    }

    public function markSse(string $path, string $channel = 'global'): void {
        $now = microtime(true);
        $this->sse = true;
        $this->ssePath = $path;
        $this->sseChannel = $channel;
        $this->keepAlive = true;
        $this->lastReadAt = $now;
        $this->lastWriteAt = $now;
        $this->lastActivity = $now;
    }

    public function isWebSocket(): bool {
        return $this->webSocket;
    }

    public function getWebSocketPath(): string {
        return $this->webSocketPath;
    }

    public function isSse(): bool {
        return $this->sse;
    }

    public function getSsePath(): string {
        return $this->ssePath;
    }

    public function getSseChannel(): string {
        return $this->sseChannel;
    }

    public function markClosing(): void {
        $this->closing = true;
    }

    public function isClosing(): bool {
        return $this->closing;
    }
}
