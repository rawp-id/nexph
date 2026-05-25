<?php
namespace Core\Database;

class QueryLogger {
    private static array $queries = [];
    private static bool $enabled = false;
    private static string $logFile = '';

    public static function enable(): void {
        self::$enabled = true;
        self::$logFile = sys_get_temp_dir() . '/nexph_queries_' . getmypid() . '.json';
    }

    public static function disable(): void {
        self::$enabled = false;
    }

    public static function log(string $sql, array $params, float $time): void {
        if (!self::$enabled) return;
        
        $entry = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time,
            'timestamp' => microtime(true)
        ];
        
        self::$queries[] = $entry;
        
        if (self::$logFile) {
            $existing = [];
            if (file_exists(self::$logFile)) {
                $existing = json_decode(file_get_contents(self::$logFile), true) ?: [];
            }
            $existing[] = $entry;
            file_put_contents(self::$logFile, json_encode($existing));
        }
    }

    public static function getQueries(): array {
        if (self::$logFile && file_exists(self::$logFile)) {
            return json_decode(file_get_contents(self::$logFile), true) ?: [];
        }
        return self::$queries;
    }

    public static function getTotalTime(): float {
        return array_sum(array_column(self::getQueries(), 'time'));
    }

    public static function getCount(): int {
        return count(self::getQueries());
    }

    public static function clear(): void {
        self::$queries = [];
        if (self::$logFile && file_exists(self::$logFile)) {
            unlink(self::$logFile);
        }
    }
}
