<?php
namespace App\Jobs;

use Core\Queue\JobHandler;

class SendNotificationJob extends JobHandler {
    public const NAME = 'send_notification';

    public function handle(array $payload, ?callable $progress = null): void {
        $userId = $payload['user_id'] ?? null;
        $message = $payload['message'] ?? '';

        if (!$userId) {
            throw new \InvalidArgumentException('User ID required');
        }

        echo "[SendNotification] Notifying user {$userId}: {$message}\n";
        usleep(200000);
        echo "[SendNotification] Done\n";
    }
}
