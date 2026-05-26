<?php
namespace Core\Queue;

use Core\Database\DB;
use Core\Runtime\Daemon;
use Core\Runtime\MemoryMonitor;

class WorkerDaemon {
    private Daemon $daemon;
    private int $maxAttempts = 3;
    private int $batchSize = 5;
    private int $sleepMs = 1000;
    private int $memoryLimit;
    private array $handlers = [];
    private int $processed = 0;
    private int $failed = 0;

    public function __construct(string $name = 'nexph-worker') {
        $this->daemon = new Daemon($name);
        $this->memoryLimit = (int) ($this->parseBytes(ini_get('memory_limit')) * 0.8);
    }

    public function register(string $jobName, string $handlerClass): self {
        $this->handlers[$jobName] = $handlerClass;
        return $this;
    }

    public function setBatchSize(int $size): self {
        $this->batchSize = $size;
        return $this;
    }

    public function setSleepMs(int $ms): self {
        $this->sleepMs = $ms;
        return $this;
    }

    public function setMaxAttempts(int $attempts): self {
        $this->maxAttempts = $attempts;
        return $this;
    }

    public function run(): void {
        $this->daemon->start();
        $this->log("Worker started");

        $this->daemon->onCycle(function ($cycle) {
            $this->processBatch();

            // Memory check
            if (memory_get_usage(true) > $this->memoryLimit) {
                $this->log("Memory limit approaching, restarting...");
                $this->daemon->shutdown();
            }
        });

        $this->daemon->onShutdown(function () {
            $this->log("Processed: {$this->processed}, Failed: {$this->failed}");
        });

        $this->daemon->run($this->sleepMs);
    }

    private function processBatch(): void {
        $jobs = DB::query(
            "SELECT id, name, payload, status, attempts, progress, created_at 
             FROM job_workers 
             WHERE status IN ('pending', 'failed') AND attempts < ? 
             ORDER BY created_at ASC 
             LIMIT ?",
            [$this->maxAttempts, $this->batchSize]
        );

        foreach ($jobs as $job) {
            if (!$this->daemon->isRunning()) break;
            $this->processJob($job);
        }
    }

    private function processJob(array $job): void {
        DB::query(
            "UPDATE job_workers SET status = 'running', attempts = attempts + 1, started_at = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), $job['id']]
        );

        try {
            $payload = json_decode($job['payload'], true) ?? [];

            if (isset($this->handlers[$job['name']])) {
                $handlerClass = $this->handlers[$job['name']];
                $handler = new $handlerClass();
                $handler->handle($payload, function ($progress) use ($job) {
                    DB::query("UPDATE job_workers SET progress = ? WHERE id = ?", [$progress, $job['id']]);
                });
            } else {
                throw new \RuntimeException("No handler for job: {$job['name']}");
            }

            DB::query(
                "UPDATE job_workers SET status = 'completed', progress = 100, completed_at = ? WHERE id = ?",
                [date('Y-m-d H:i:s'), $job['id']]
            );
            $this->processed++;
        } catch (\Throwable $e) {
            $attempts = $job['attempts'] + 1;
            $status = ($attempts >= $this->maxAttempts) ? 'failed' : 'pending';
            DB::query(
                "UPDATE job_workers SET status = ?, error = ? WHERE id = ?",
                [$status, $e->getMessage(), $job['id']]
            );
            if ($status === 'failed') $this->failed++;
            $this->log("Job {$job['id']} error: " . $e->getMessage());
        }
    }

    public function getStats(): array {
        return [
            'processed' => $this->processed,
            'failed' => $this->failed,
            'uptime' => $this->daemon->uptime(),
            'cycles' => $this->daemon->cycles(),
            'memory' => $this->daemon->getMemoryMonitor()->getStats(),
        ];
    }

    private function parseBytes(string $val): int {
        $val = trim($val);
        if ($val === '-1') return PHP_INT_MAX;
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;
        switch ($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    private function log(string $message): void {
        $time = date('Y-m-d H:i:s');
        error_log("[{$time}] [Worker] {$message}");
    }
}
