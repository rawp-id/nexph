<?php
namespace App\Jobs;

use Core\Queue\JobHandler;

class SendEmailJob extends JobHandler {
    public const NAME = 'send_email';

    public function handle(array $payload, ?callable $progress = null): void {
        $to = $payload['to'] ?? null;
        $subject = $payload['subject'] ?? 'No Subject';

        if (!$to) {
            throw new \InvalidArgumentException('Email recipient required');
        }

        echo "[SendEmail] Sending to {$to}: {$subject}\n";
        usleep(300000);
        echo "[SendEmail] Done\n";
    }
}
