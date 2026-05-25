<?php
namespace Core\Event;

abstract class Event {
    public readonly float $timestamp;
    public readonly string $id;
    private bool $propagate = true;

    public function __construct() {
        $this->timestamp = microtime(true);
        $this->id = bin2hex(random_bytes(8));
    }

    public function stopPropagation(): void {
        $this->propagate = false;
    }

    public function shouldPropagate(): bool {
        return $this->propagate;
    }

    public function getName(): string {
        return static::class;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->getName(),
            'timestamp' => $this->timestamp,
        ];
    }
}

// Common events
class UserCreated extends Event {
    public function __construct(public readonly int $userId, public readonly array $data) {
        parent::__construct();
    }
}

class UserUpdated extends Event {
    public function __construct(public readonly int $userId, public readonly array $changes) {
        parent::__construct();
    }
}

class UserDeleted extends Event {
    public function __construct(public readonly int $userId) {
        parent::__construct();
    }
}

class ModelCreated extends Event {
    public function __construct(public readonly string $table, public readonly int $modelId, public readonly array $data) {
        parent::__construct();
    }
}

class ModelUpdated extends Event {
    public function __construct(public readonly string $table, public readonly int $modelId, public readonly array $changes) {
        parent::__construct();
    }
}

class ModelDeleted extends Event {
    public function __construct(public readonly string $table, public readonly int $modelId) {
        parent::__construct();
    }
}

class RequestReceived extends Event {
    public function __construct(public readonly string $method, public readonly string $uri) {
        parent::__construct();
    }
}

class ResponseSent extends Event {
    public function __construct(public readonly int $status, public readonly float $duration) {
        parent::__construct();
    }
}

class JobQueued extends Event {
    public function __construct(public readonly string $name, public readonly array $payload) {
        parent::__construct();
    }
}

class JobCompleted extends Event {
    public function __construct(public readonly int $jobId, public readonly string $name) {
        parent::__construct();
    }
}

class JobFailed extends Event {
    public function __construct(public readonly int $jobId, public readonly string $name, public readonly string $error) {
        parent::__construct();
    }
}
