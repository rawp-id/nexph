<?php
namespace Core\Health;

use Core\Database\DB;
use Core\Runtime\ConnectionPool;

class HealthCheck {
    private static int $startTime = 0;
    private static array $checks = [];

    public static function init(): void {
        self::$startTime = time();
    }

    public static function register(string $name, callable $check): void {
        self::$checks[$name] = $check;
    }

    public static function run(): array {
        $results = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'uptime' => self::$startTime > 0 ? time() - self::$startTime : 0,
            'checks' => [],
        ];

        // Core checks
        $results['checks']['memory'] = self::checkMemory();
        $results['checks']['database'] = self::checkDatabase();
        $results['checks']['storage'] = self::checkStorage();
        $results['checks']['connections'] = self::checkConnections();

        // Custom checks
        foreach (self::$checks as $name => $check) {
            try {
                $results['checks'][$name] = $check();
            } catch (\Throwable $e) {
                $results['checks'][$name] = ['status' => 'error', 'error' => $e->getMessage()];
            }
        }

        // Overall status
        foreach ($results['checks'] as $check) {
            if (($check['status'] ?? 'ok') !== 'ok') {
                $results['status'] = 'degraded';
                break;
            }
        }

        return $results;
    }

    public static function liveness(): array {
        return [
            'status' => 'ok',
            'timestamp' => date('c'),
        ];
    }

    public static function readiness(): array {
        $dbOk = self::checkDatabase()['status'] === 'ok';
        $storageOk = self::checkStorage()['status'] === 'ok';

        return [
            'status' => ($dbOk && $storageOk) ? 'ok' : 'not_ready',
            'timestamp' => date('c'),
            'database' => $dbOk,
            'storage' => $storageOk,
        ];
    }

    private static function checkMemory(): array {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = self::parseBytes(ini_get('memory_limit'));

        $usagePercent = $limit > 0 ? ($usage / $limit) * 100 : 0;
        $status = $usagePercent > 90 ? 'critical' : ($usagePercent > 70 ? 'warning' : 'ok');

        return [
            'status' => $status,
            'usage' => $usage,
            'peak' => $peak,
            'limit' => $limit,
            'usage_percent' => round($usagePercent, 2),
        ];
    }

    private static function checkDatabase(): array {
        try {
            $start = microtime(true);
            DB::query("SELECT 1");
            $latency = (microtime(true) - $start) * 1000;

            return [
                'status' => $latency > 100 ? 'warning' : 'ok',
                'latency_ms' => round($latency, 2),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private static function checkStorage(): array {
        $path = dirname(__DIR__, 2) . '/storage';
        if (!is_dir($path)) {
            return ['status' => 'error', 'error' => 'Storage directory not found'];
        }
        if (!is_writable($path)) {
            return ['status' => 'error', 'error' => 'Storage not writable'];
        }

        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $usedPercent = $total > 0 ? (($total - $free) / $total) * 100 : 0;

        return [
            'status' => $usedPercent > 95 ? 'critical' : ($usedPercent > 80 ? 'warning' : 'ok'),
            'free_bytes' => $free,
            'total_bytes' => $total,
            'used_percent' => round($usedPercent, 2),
        ];
    }

    private static function checkConnections(): array {
        return [
            'status' => 'ok',
            'pool' => ConnectionPool::stats(),
        ];
    }

    private static function parseBytes(string $val): int {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;
        switch ($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
}
