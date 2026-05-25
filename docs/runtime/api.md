# Runtime API Reference

Complete API documentation for Nexph Runtime layer.

## Runtime

Main facade for runtime features.

### Runtime::init()

Initialize runtime and detect capabilities.

```php
Runtime::init();
```

**Returns:** `void`

**Note:** Called automatically on first use.

---

### Runtime::available()

Check if runtime is available (CLI + Fibers).

```php
$available = Runtime::available();
```

**Returns:** `bool`

**Example:**
```php
if (Runtime::available()) {
    // Use async features
} else {
    // Use sync fallback
}
```

---

### Runtime::capabilities()

Get runtime capabilities.

```php
$caps = Runtime::capabilities();
```

**Returns:** `array`

**Structure:**
```php
[
    'fibers' => bool,   // PHP 8.1+ Fiber support
    'pcntl' => bool,    // Process control extension
    'sockets' => bool,  // Sockets extension
    'posix' => bool,    // POSIX functions
    'redis' => bool,    // Redis extension
    'cli' => bool,      // CLI SAPI
]
```

---

### Runtime::spawn()

Spawn a coroutine (Fiber-based).

```php
$coro = Runtime::spawn(callable $fn, mixed ...$args): Coroutine
```

**Parameters:**
- `$fn` — Callable to execute
- `...$args` — Arguments to pass

**Returns:** `Coroutine`

**Fallback:** Executes synchronously if runtime unavailable

**Example:**
```php
$coro = Runtime::spawn(function($name) {
    echo "Hello, {$name}\n";
    Runtime::sleep(1.0);
    return "Done";
}, 'World');

$result = $coro->await();
```

---

### Runtime::sleep()

Cooperative sleep (yields to event loop).

```php
Runtime::sleep(float $seconds): void
```

**Parameters:**
- `$seconds` — Sleep duration in seconds

**Returns:** `void`

**Fallback:** Blocking sleep if runtime unavailable

**Example:**
```php
Runtime::spawn(function() {
    echo "Before sleep\n";
    Runtime::sleep(2.0);
    echo "After 2 seconds\n";
});
```

---

### Runtime::yield()

Yield control to event loop.

```php
Runtime::yield(): void
```

**Returns:** `void`

**Example:**
```php
Runtime::spawn(function() {
    for ($i = 0; $i < 1000; $i++) {
        // Do work
        if ($i % 100 === 0) {
            Runtime::yield(); // Let other coroutines run
        }
    }
});
```

---

### Runtime::run()

Run event loop until all coroutines complete.

```php
Runtime::run(): void
```

**Returns:** `void`

**Example:**
```php
Runtime::spawn(function() {
    echo "Task 1\n";
});

Runtime::spawn(function() {
    echo "Task 2\n";
});

Runtime::run(); // Blocks until both complete
```

---

### Runtime::stop()

Stop event loop.

```php
Runtime::stop(): void
```

**Returns:** `void`

**Example:**
```php
Runtime::spawn(function() {
    Runtime::sleep(5.0);
    Runtime::stop();
});

Runtime::run(); // Stops after 5 seconds
```

---

### Runtime::loop()

Get or create event loop instance.

```php
$loop = Runtime::loop(): EventLoop
```

**Returns:** `EventLoop`

**Throws:** `RuntimeException` if runtime unavailable

---

## Coroutine

Wrapper around PHP Fiber with lifecycle management.

### Coroutine::resume()

Resume coroutine execution.

```php
$result = $coro->resume(mixed $value = null): mixed
```

**Parameters:**
- `$value` — Value to send to fiber

**Returns:** `mixed` — Result from fiber

---

### Coroutine::isFinished()

Check if coroutine is finished.

```php
$finished = $coro->isFinished(): bool
```

**Returns:** `bool`

---

### Coroutine::await()

Wait for coroutine to complete (blocking).

```php
$result = $coro->await(): mixed
```

**Returns:** `mixed` — Final result

**Example:**
```php
$coro = Runtime::spawn(function() {
    Runtime::sleep(1.0);
    return 42;
});

$result = $coro->await(); // Blocks until complete
echo $result; // 42
```

---

### Coroutine::fiber()

Get underlying Fiber instance.

```php
$fiber = $coro->fiber(): ?Fiber
```

**Returns:** `Fiber|null`

---

## EventLoop

Lightweight cooperative scheduler.

### EventLoop::schedule()

Schedule a coroutine for execution.

```php
$loop->schedule(Coroutine $coroutine): void
```

**Parameters:**
- `$coroutine` — Coroutine to schedule

---

### EventLoop::sleepFiber()

Schedule fiber to wake after delay.

```php
$loop->sleepFiber(Fiber $fiber, float $seconds): void
```

**Parameters:**
- `$fiber` — Fiber to sleep
- `$seconds` — Sleep duration

---

### EventLoop::timer()

Schedule a timer callback.

```php
$id = $loop->timer(float $seconds, callable $callback, bool $repeat = false): int
```

**Parameters:**
- `$seconds` — Delay in seconds
- `$callback` — Function to call
- `$repeat` — Whether to repeat

**Returns:** `int` — Timer ID

---

### EventLoop::cancelTimer()

Cancel a timer.

```php
$loop->cancelTimer(int $id): void
```

**Parameters:**
- `$id` — Timer ID to cancel

---

### EventLoop::run()

Run event loop until all work complete.

```php
$loop->run(): void
```

---

### EventLoop::stop()

Stop event loop.

```php
$loop->stop(): void
```

---

## Channel

Message passing between coroutines.

### new Channel()

Create a channel.

```php
$ch = new Channel(int $capacity = 0)
```

**Parameters:**
- `$capacity` — Buffer size (0 = unbuffered)

**Example:**
```php
$ch = new Channel(10); // Buffered
$ch = new Channel(0);  // Unbuffered
```

---

### Channel::send()

Send value to channel (blocking).

```php
$ch->send(mixed $value): void
```

**Parameters:**
- `$value` — Value to send

**Blocks:** If buffer full

**Example:**
```php
Runtime::spawn(function() use ($ch) {
    $ch->send("Hello");
    $ch->send("World");
});
```

---

### Channel::receive()

Receive value from channel (blocking).

```php
$value = $ch->receive(): mixed
```

**Returns:** `mixed` — Received value

**Blocks:** If buffer empty

**Example:**
```php
Runtime::spawn(function() use ($ch) {
    $msg = $ch->receive();
    echo "Received: {$msg}\n";
});
```

---

### Channel::trySend()

Try to send without blocking.

```php
$success = $ch->trySend(mixed $value): bool
```

**Parameters:**
- `$value` — Value to send

**Returns:** `bool` — Success status

---

### Channel::tryReceive()

Try to receive without blocking.

```php
$value = $ch->tryReceive(): mixed
```

**Returns:** `mixed|null` — Value or null if empty

---

### Channel::close()

Close channel.

```php
$ch->close(): void
```

**Example:**
```php
Runtime::spawn(function() use ($ch) {
    for ($i = 0; $i < 10; $i++) {
        $ch->send($i);
    }
    $ch->close();
});

Runtime::spawn(function() use ($ch) {
    while (($val = $ch->receive()) !== null) {
        echo "Got: {$val}\n";
    }
});
```

---

## Worker

Long-running worker process manager.

### Worker::start()

Start worker with callback.

```php
Worker::start(callable $callback, array $options = []): void
```

**Parameters:**
- `$callback` — Function to execute each iteration
- `$options` — Configuration options

**Options:**
```php
[
    'sleep' => float,           // Sleep between iterations (default: 1.0)
    'max_iterations' => int,    // Max iterations (0 = infinite)
]
```

**Example:**
```php
Worker::start(function() {
    echo "Processing...\n";
}, [
    'sleep' => 2.0,
    'max_iterations' => 100,
]);
```

---

### Worker::stop()

Stop worker gracefully.

```php
Worker::stop(): void
```

---

### Worker::fork()

Fork worker process (requires pcntl).

```php
$pid = Worker::fork(callable $callback): int
```

**Parameters:**
- `$callback` — Function to execute in child

**Returns:** `int` — Child PID

**Throws:** `RuntimeException` if pcntl unavailable

**Example:**
```php
$pid = Worker::fork(function() {
    // Child process
    echo "Worker PID: " . getmypid() . "\n";
});

// Parent process
echo "Spawned worker: {$pid}\n";
```

---

## Timer

Timer management.

### Timer::after()

Schedule callback after delay.

```php
$id = Timer::after(float $seconds, callable $callback): int
```

**Parameters:**
- `$seconds` — Delay in seconds
- `$callback` — Function to call

**Returns:** `int` — Timer ID

**Example:**
```php
Timer::after(5.0, function() {
    echo "5 seconds elapsed\n";
});
```

---

### Timer::every()

Schedule repeating callback.

```php
$id = Timer::every(float $seconds, callable $callback): int
```

**Parameters:**
- `$seconds` — Interval in seconds
- `$callback` — Function to call

**Returns:** `int` — Timer ID

**Example:**
```php
$id = Timer::every(1.0, function() {
    echo "Tick\n";
});

// Cancel after 10 seconds
Timer::after(10.0, function() use ($id) {
    Timer::cancel($id);
});
```

---

### Timer::cancel()

Cancel timer.

```php
Timer::cancel(int $id): void
```

**Parameters:**
- `$id` — Timer ID to cancel

---

### Timer::defer()

Defer callback to next tick.

```php
Timer::defer(callable $callback): void
```

**Parameters:**
- `$callback` — Function to call

**Example:**
```php
Timer::defer(function() {
    echo "Next tick\n";
});
```

---

## Socket

Socket wrapper for async I/O.

### Socket::tcp()

Create TCP socket.

```php
$socket = Socket::tcp(): ?Socket
```

**Returns:** `Socket|null` — Socket or null if unavailable

---

### Socket::udp()

Create UDP socket.

```php
$socket = Socket::udp(): ?Socket
```

**Returns:** `Socket|null` — Socket or null if unavailable

---

### Socket::setNonBlocking()

Set non-blocking mode.

```php
$success = $socket->setNonBlocking(bool $enable = true): bool
```

**Parameters:**
- `$enable` — Enable non-blocking

**Returns:** `bool` — Success status

---

### Socket::bind()

Bind socket to address.

```php
$success = $socket->bind(string $address, int $port): bool
```

**Parameters:**
- `$address` — IP address
- `$port` — Port number

**Returns:** `bool` — Success status

---

### Socket::listen()

Listen for connections.

```php
$success = $socket->listen(int $backlog = 128): bool
```

**Parameters:**
- `$backlog` — Connection queue size

**Returns:** `bool` — Success status

---

### Socket::accept()

Accept connection.

```php
$client = $socket->accept(): ?Socket
```

**Returns:** `Socket|null` — Client socket or null

---

### Socket::connect()

Connect to remote address.

```php
$success = $socket->connect(string $address, int $port): bool
```

**Parameters:**
- `$address` — Remote IP
- `$port` — Remote port

**Returns:** `bool` — Success status

---

### Socket::read()

Read data from socket.

```php
$data = $socket->read(int $length = 8192): string|false
```

**Parameters:**
- `$length` — Max bytes to read

**Returns:** `string|false` — Data or false on error

---

### Socket::write()

Write data to socket.

```php
$bytes = $socket->write(string $data): int|false
```

**Parameters:**
- `$data` — Data to write

**Returns:** `int|false` — Bytes written or false

---

### Socket::close()

Close socket.

```php
$socket->close(): void
```

---

### Socket::getError()

Get last error.

```php
$error = $socket->getError(): string
```

**Returns:** `string` — Error message

---

## Error Handling

All runtime functions handle errors gracefully:

```php
try {
    Runtime::spawn(function() {
        throw new \Exception("Error in coroutine");
    });
    
    Runtime::run();
} catch (\Throwable $e) {
    // Errors are logged, loop continues
    echo "Error: " . $e->getMessage();
}
```

## Type Hints

All classes use strict types:

```php
declare(strict_types=1);

namespace Runtime;

class Runtime {
    public static function spawn(callable $fn, mixed ...$args): Coroutine
    {
        // ...
    }
}
```
