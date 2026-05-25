<?php
namespace App\Jobs;

use Core\Queue\JobHandler;

class ProcessImageJob extends JobHandler {
    public const NAME = 'process_image';

    public function handle(array $payload, ?callable $progress = null): void {
        $imagePath = $payload['path'] ?? null;
        $operations = $payload['operations'] ?? ['resize'];

        if (!$imagePath) {
            throw new \InvalidArgumentException('Image path required');
        }

        echo "[ProcessImage] Processing {$imagePath}\n";

        $total = count($operations);
        foreach ($operations as $i => $operation) {
            echo "[ProcessImage] Applying {$operation}\n";
            usleep(200000);
            $this->progress($progress, (int) (($i + 1) / $total * 100));
        }

        echo "[ProcessImage] Done\n";
    }
}
