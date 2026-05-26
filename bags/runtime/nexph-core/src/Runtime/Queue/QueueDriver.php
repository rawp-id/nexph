<?php
namespace Core\Runtime\Queue;

/**
 * Queue driver interface.
 * 
 * Implementations: memory, file, database, APCu, Redis.
 */
interface QueueDriver {
    /**
     * Push job to queue.
     */
    public function push(Job $job): void;
    
    /**
     * Pop next available job from queue.
     */
    public function pop(): ?Job;
    
    /**
     * Update job status.
     */
    public function update(Job $job): void;
    
    /**
     * Get job by ID.
     */
    public function get(string $id): ?Job;
    
    /**
     * Delete job.
     */
    public function delete(string $id): void;
    
    /**
     * Get queue depth (pending jobs).
     */
    public function depth(): int;
    
    /**
     * Push job to dead letter queue.
     */
    public function pushDeadLetter(Job $job): void;
    
    /**
     * Get dead letter queue jobs.
     */
    public function getDeadLetters(int $limit = 100): array;
    
    /**
     * Clear all jobs.
     */
    public function clear(): void;
}
