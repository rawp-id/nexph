<?php
namespace Core\Log;

class Logger {
    private static string $channel = 'app';
    private static string $level = 'debug';
    private static array $handlers = [];
    private static array $processors = [];
    private static array $context = [];

    public const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5,
        'alert' => 6,
        'emergency' => 7,
    ];

    public static function configure(array $config): void {
        self::$channel = $config['channel'] ?? 'app';
        self::$level = $config['level'] ?? 'debug';

        foreach ($config['handlers'] ?? [] as $handler) {
            self::addHandler($handler);
        }
    }

    public static function addHandler(LogHandler $handler): void {
        self::$handlers[] = $handler;
    }

    public static function addProcessor(callable $processor): void {
        self::$processors[] = $processor;
    }

    public static function setContext(array $context): void {
        self::$context = array_merge(self::$context, $context);
    }

    public static function log(string $level, string $message, array $context = []): void {
        if (self::LEVELS[$level] < self::LEVELS[self::$level]) {
            return;
        }

        $record = [
            'timestamp' => date('c'),
            'level' => $level,
            'channel' => self::$channel,
            'message' => $message,
            'context' => array_merge(self::$context, $context),
            'extra' => [
                'memory' => memory_get_usage(true),
                'pid' => getmypid(),
            ],
        ];

        foreach (self::$processors as $processor) {
            $record = $processor($record);
        }

        foreach (self::$handlers as $handler) {
            $handler->handle($record);
        }

        // Fallback if no handlers
        if (empty(self::$handlers)) {
            error_log(self::formatLine($record));
        }
    }

    private static function formatLine(array $record): string {
        $ctx = !empty($record['context']) ? ' ' . json_encode($record['context']) : '';
        return "[{$record['timestamp']}] {$record['channel']}.{$record['level']}: {$record['message']}{$ctx}";
    }

    public static function debug(string $message, array $context = []): void {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void {
        self::log('info', $message, $context);
    }

    public static function notice(string $message, array $context = []): void {
        self::log('notice', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::log('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void {
        self::log('critical', $message, $context);
    }

    public static function alert(string $message, array $context = []): void {
        self::log('alert', $message, $context);
    }

    public static function emergency(string $message, array $context = []): void {
        self::log('emergency', $message, $context);
    }
}

interface LogHandler {
    public function handle(array $record): void;
}
