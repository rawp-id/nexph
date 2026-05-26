<?php
namespace Core\Runtime;

use Fiber;

/**
 * Lightweight channel for coroutine communication.
 * 
 * Supports buffered and unbuffered channels.
 * Blocking send/receive with cooperative yielding.
 */
class Channel {
    private array $buffer = [];
    private int $bufferHead = 0;
    private int $capacity;
    private array $sendQueue = [];
    private int $sendHead = 0;
    private array $recvQueue = [];
    private int $recvHead = 0;
    private bool $closed = false;
    
    public function __construct(int $capacity = 0) {
        $this->capacity = max(0, $capacity);
    }
    
    /**
     * Send value to channel.
     * Blocks if buffer full (cooperative yield).
     */
    public function send(mixed $value): void {
        if ($this->closed) {
            return;
        }
        
        // Check if receiver waiting (unbuffered or immediate delivery)
        if ($this->queueCount($this->recvQueue, $this->recvHead) > 0) {
            $receiver = $this->dequeue($this->recvQueue, $this->recvHead);
            $this->resumeFiber($receiver['fiber'], $value);
            return;
        }
        
        // Add to buffer if capacity allows
        if ($this->capacity > 0 && $this->queueCount($this->buffer, $this->bufferHead) < $this->capacity) {
            $this->buffer[] = $value;
            return;
        }
        
        // Unbuffered channel with no receiver, block sender
        if ($this->capacity === 0) {
            $fiber = Fiber::getCurrent();
            if ($fiber === null) {
                throw new \RuntimeException('Channel send must be called from within a fiber');
            }
            
            $this->sendQueue[] = ['fiber' => $fiber, 'value' => $value];
            Fiber::suspend(FiberCoroutine::SUSPEND_CHANNEL);
            return;
        }
        
        // Buffer full, block sender
        $fiber = Fiber::getCurrent();
        if ($fiber === null) {
            throw new \RuntimeException('Channel send must be called from within a fiber');
        }
        
        $this->sendQueue[] = ['fiber' => $fiber, 'value' => $value];
        Fiber::suspend(FiberCoroutine::SUSPEND_CHANNEL);
    }
    
    /**
     * Receive value from channel.
     * Blocks if buffer empty (cooperative yield).
     */
    public function receive(): mixed {
        // If buffer has data, return immediately
        if ($this->queueCount($this->buffer, $this->bufferHead) > 0) {
            $value = $this->dequeue($this->buffer, $this->bufferHead);
            
            // Wake blocked sender if any
            if ($this->queueCount($this->sendQueue, $this->sendHead) > 0) {
                $sender = $this->dequeue($this->sendQueue, $this->sendHead);
                $this->buffer[] = $sender['value'];
                $this->resumeFiber($sender['fiber']);
            }
            
            return $value;
        }
        
        // Check if sender waiting (unbuffered)
        if ($this->queueCount($this->sendQueue, $this->sendHead) > 0) {
            $sender = $this->dequeue($this->sendQueue, $this->sendHead);
            $this->resumeFiber($sender['fiber']);
            return $sender['value'];
        }
        
        // Channel closed and empty
        if ($this->closed) {
            return null;
        }
        
        // Buffer empty, block receiver
        $fiber = Fiber::getCurrent();
        if ($fiber === null) {
            throw new \RuntimeException('Channel receive must be called from within a fiber');
        }
        
        $this->recvQueue[] = ['fiber' => $fiber];
        return Fiber::suspend(FiberCoroutine::SUSPEND_CHANNEL);
    }
    
    /**
     * Try to send without blocking.
     */
    public function trySend(mixed $value): bool {
        if ($this->closed) {
            return false;
        }
        
        if ($this->queueCount($this->buffer, $this->bufferHead) < $this->capacity || $this->queueCount($this->recvQueue, $this->recvHead) > 0) {
            $this->send($value);
            return true;
        }
        return false;
    }
    
    /**
     * Try to receive without blocking.
     */
    public function tryReceive(): mixed {
        if ($this->queueCount($this->buffer, $this->bufferHead) > 0 || $this->queueCount($this->sendQueue, $this->sendHead) > 0) {
            return $this->receive();
        }
        return null;
    }
    
    /**
     * Close channel.
     */
    public function close(): void {
        $this->closed = true;
        
        // Wake all blocked fibers
        for ($i = $this->sendHead, $n = count($this->sendQueue); $i < $n; $i++) {
            $sender = $this->sendQueue[$i];
            $this->resumeFiber($sender['fiber']);
        }
        
        for ($i = $this->recvHead, $n = count($this->recvQueue); $i < $n; $i++) {
            $receiver = $this->recvQueue[$i];
            $this->resumeFiber($receiver['fiber'], null);
        }
        
        $this->sendQueue = [];
        $this->recvQueue = [];
        $this->buffer = [];
        $this->bufferHead = 0;
        $this->sendHead = 0;
        $this->recvHead = 0;
    }

    private function queueCount(array $queue, int $head): int {
        return count($queue) - $head;
    }

    private function dequeue(array &$queue, int &$head): mixed {
        $value = $queue[$head++];

        if ($head > 64 && $head * 2 >= count($queue)) {
            $queue = array_slice($queue, $head);
            $head = 0;
        }

        return $value;
    }

    private function resumeFiber(Fiber $fiber, mixed $value = null): void {
        if (!$fiber->isSuspended()) {
            return;
        }

        $signal = $fiber->resume($value);
        if ($fiber->isSuspended() && $signal === FiberCoroutine::SUSPEND_YIELD && Runtime::available()) {
            $coroutine = new FiberCoroutine($fiber);
            $coroutine->markStarted();
            Runtime::loop()->schedule($coroutine);
        }
    }
}
