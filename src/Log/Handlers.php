<?php
namespace Core\Log;

class FileHandler implements LogHandler {
    private string $path;
    private int $maxSize;
    private int $maxFiles;
    private bool $json;

    public function __construct(string $path, int $maxSize = 10485760, int $maxFiles = 5, bool $json = false) {
        $this->path = $path;
        $this->maxSize = $maxSize;
        $this->maxFiles = $maxFiles;
        $this->json = $json;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function handle(array $record): void {
        $this->rotate();

        $line = $this->json ? $this->formatJson($record) : $this->formatLine($record);
        file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function formatLine(array $record): string {
        $ctx = !empty($record['context']) ? ' ' . json_encode($record['context']) : '';
        return "[{$record['timestamp']}] {$record['channel']}.{$record['level']}: {$record['message']}{$ctx}";
    }

    private function formatJson(array $record): string {
        return json_encode($record, JSON_UNESCAPED_SLASHES);
    }

    private function rotate(): void {
        if (!file_exists($this->path) || filesize($this->path) < $this->maxSize) {
            return;
        }

        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $old = "{$this->path}.{$i}";
            $new = "{$this->path}." . ($i + 1);
            if (file_exists($old)) {
                if ($i + 1 > $this->maxFiles) {
                    unlink($old);
                } else {
                    rename($old, $new);
                }
            }
        }

        rename($this->path, "{$this->path}.1");
    }
}

class StdoutHandler implements LogHandler {
    private bool $json;

    public function __construct(bool $json = true) {
        $this->json = $json;
    }

    public function handle(array $record): void {
        if ($this->json) {
            echo json_encode($record, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        } else {
            $ctx = !empty($record['context']) ? ' ' . json_encode($record['context']) : '';
            echo "[{$record['timestamp']}] {$record['channel']}.{$record['level']}: {$record['message']}{$ctx}" . PHP_EOL;
        }
    }
}

class SyslogHandler implements LogHandler {
    private string $ident;
    private int $facility;
    private static array $levelMap = [
        'debug' => LOG_DEBUG,
        'info' => LOG_INFO,
        'notice' => LOG_NOTICE,
        'warning' => LOG_WARNING,
        'error' => LOG_ERR,
        'critical' => LOG_CRIT,
        'alert' => LOG_ALERT,
        'emergency' => LOG_EMERG,
    ];

    public function __construct(string $ident = 'nexph', int $facility = LOG_USER) {
        $this->ident = $ident;
        $this->facility = $facility;
        openlog($this->ident, LOG_PID, $this->facility);
    }

    public function handle(array $record): void {
        $priority = self::$levelMap[$record['level']] ?? LOG_INFO;
        $ctx = !empty($record['context']) ? ' ' . json_encode($record['context']) : '';
        syslog($priority, "{$record['message']}{$ctx}");
    }

    public function __destruct() {
        closelog();
    }
}

class BufferedHandler implements LogHandler {
    private LogHandler $handler;
    private array $buffer = [];
    private int $bufferSize;

    public function __construct(LogHandler $handler, int $bufferSize = 100) {
        $this->handler = $handler;
        $this->bufferSize = $bufferSize;
    }

    public function handle(array $record): void {
        $this->buffer[] = $record;
        if (count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    public function flush(): void {
        foreach ($this->buffer as $record) {
            $this->handler->handle($record);
        }
        $this->buffer = [];
    }

    public function __destruct() {
        $this->flush();
    }
}
