<?php
namespace Core\Runtime\Queue;

/**
 * Job status constants.
 */
class JobStatus {
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const RETRYING = 'retrying';
}
