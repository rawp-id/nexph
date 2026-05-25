<?php
namespace App\Jobs;

use Core\Queue\JobHandler;

class ProcessWebhookJob extends JobHandler {
    public const NAME = 'process_webhook';

    public function handle(array $payload, ?callable $progress = null): void {
        $url = $payload['url'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$url) {
            throw new \InvalidArgumentException('Webhook URL required');
        }

        echo "[ProcessWebhook] Sending to {$url}\n";
        usleep(500000);
        echo "[ProcessWebhook] Done\n";
    }
}
