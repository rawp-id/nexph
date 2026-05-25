# Nexph Runtime Test Suite

Comprehensive testing suite for validating runtime hardening, stability, and performance.

## Quick Start

```bash
# Quick validation (3 tests, ~30 seconds)
./quick_test.sh

# Full test suite (all tests, ~5 minutes)
./run_all_tests.sh

# Long running stability (default: 5 minutes)
./run_long_test.sh 5

# Memory leak detection (default: 60 minutes)
./run_memory_test.sh 60
```

## Test Categories

### Core Tests
- **IntegrationTest.php** - Basic functionality validation (10 tests)

### Stress Tests
- **StressTest.php** - Original stress test (12 tests)
- **ComprehensiveStressTest.php** - Extended stress test (20 tests)
  - Massive coroutine spawn (10,000)
  - High volume channel messaging (100,000 msgs)
  - Timer storm (1,000 timers)
  - Concurrent task execution (1,000 parallel)
  - Deadlock detection
  - Orphaned fiber cleanup
  - Unhandled exceptions
  - Coroutine cancellation
  - Resource cleanup
  - Memory leak detection (10k iterations)
  - Long running stability (1000 iterations)
  - Event loop blocking detection
  - Channel synchronization
  - Nested coroutines
  - Rapid spawn/destroy (5000 cycles)
  - Channel backpressure
  - Timer precision
  - Fiber state transitions
  - Concurrent channel access
  - Memory pressure

### Performance Tests
- **BenchmarkTest.php** - Original benchmark (6 metrics)
- **PerformanceBenchmark.php** - Extended benchmark (10 metrics)
  - Coroutine spawn cost
  - Context switch overhead
  - Channel throughput
  - Timer accuracy
  - Event loop latency (avg, p50, p95, p99)
  - Memory per coroutine
  - Concurrent execution speedup
  - Channel latency
  - Timer overhead
  - Yield cost

### Safety Tests
- **ConcurrencySafetyTest.php** - Concurrency validation (8 tests)
  - Race condition detection
  - Atomic operations
  - Channel safety
  - Shared state access
  - Message ordering
  - Concurrent reads
  - Concurrent writes
  - Producer-consumer pattern

- **PanicRecoveryTest.php** - Exception handling (4 tests)
- **DeadlockDetector.php** - Deadlock detection (5 tests)
  - Simple deadlock
  - Circular deadlock
  - Channel deadlock
  - Nested deadlock
  - Partial deadlock

### Lifecycle Tests
- **WorkerLifecycleTest.php** - Worker lifecycle (4 tests)
- **GracefulShutdownTest.php** - Signal handling (4 tests)
  - SIGTERM handling
  - SIGINT handling
  - Cleanup on shutdown
  - Multiple signals
- **SignalTest.php** - Signal processing (2 tests)

### Long Running Tests
- **LongRunningTest.php** - Stability over time (configurable duration)
- **MemoryLeakDetector.php** - Memory leak detection (configurable duration)

## Test Results Format

### Quick Test Output
```
=== Quick Runtime Validation ===

1. Integration Test (basic functionality)...
✓ Test 1: Capability Detection
✓ Test 2: Basic Coroutine
...
All Tests Passed ✓

2. Stress Test (10k coroutines, 100k messages)...
Test 1: Massive Coroutine Spawn (10,000)... ✓
Test 2: High Volume Channel (100,000 msgs)... ✓
...
✓ All stress tests passed

3. Concurrency Safety...
Test 1: Race Condition Detection... ✓
...
✓ All concurrency safety tests passed

✓ Quick validation passed
```

### Full Test Output
```
=== Nexph Runtime Test Suite ===
Date: Tue May 13 13:47:52 UTC 2026
PHP: PHP 8.5.3 (cli)

=== Core Tests ===
Running: Integration Test
✓ All Tests Passed

=== Stress Tests ===
Running: Stress Test
✓ All stress tests passed

Running: Comprehensive Stress Test
✓ All comprehensive stress tests passed

=== Performance Tests ===
Running: Benchmark Test
✓ Benchmark complete

Running: Performance Benchmark
✓ Benchmark complete

=== Safety Tests ===
Running: Concurrency Safety Test
✓ All concurrency safety tests passed

Running: Panic Recovery Test
✓ All panic recovery tests passed

Running: Deadlock Detector
✓ All deadlock detection tests passed

=== Lifecycle Tests ===
Running: Worker Lifecycle Test
✓ All worker lifecycle tests passed

Running: Graceful Shutdown Test
✓ All graceful shutdown tests passed

Running: Signal Test
✓ All signal tests passed

=== Summary ===
Passed: 11
Failed: 0
Total: 11

✓ All tests passed
```

### Performance Benchmark Output
```
=== Nexph Runtime Performance Benchmark ===
PHP: 8.5.3
Date: 2026-05-13 13:47:52

Benchmark 1: Coroutine Spawn Cost... ✓
  Total: 0.523s
  Per spawn: 0.0523ms
  Throughput: 19120 spawns/s

Benchmark 2: Context Switch Overhead... ✓
  Total: 0.234s
  Per switch: 0.0117ms
  Switches/s: 85470

...

=== Performance Report ===

Latency Metrics:
  Coroutine spawn: 0.0523ms
  Context switch: 0.0117ms
  Yield: 0.0089ms
  Channel latency: 0.0234ms
  Timer overhead: 0.0156ms
  Timer accuracy: 0.45ms
  Loop latency (avg): 0.523ms
  Loop latency (p99): 1.234ms

Throughput Metrics:
  Coroutine spawns: 19,120/s
  Context switches: 85,470/s
  Channel messages: 52,340/s

Memory Metrics:
  Per coroutine: 2.34 KB
  Peak usage: 12.45 MB

Concurrency:
  Speedup: 9.8x

Results saved to: benchmark_results_20260513_134752.json

✓ Benchmark complete
```

### Memory Leak Detection Output
```
=== Memory Leak Detection Test ===
Duration: 60 minutes
Start: 2026-05-13 13:47:52

[13:47:52] Elapsed: 0s | Memory: 8.23 MB | Peak: 8.45 MB
[13:48:02] Elapsed: 10s | Memory: 8.25 MB | Peak: 8.47 MB
[13:48:12] Elapsed: 20s | Memory: 8.26 MB | Peak: 8.48 MB
...

=== Memory Leak Analysis ===
Initial memory: 8.23 MB
Final memory: 8.67 MB
Total growth: 0.44 MB
Growth rate: 0.12 KB/s
Peak memory: 9.12 MB
Memory trend: 0.08 KB/s
Memory stable: YES

✓ No memory leak detected
```

## Individual Test Execution

```bash
# Core
php IntegrationTest.php

# Stress
php StressTest.php
php ComprehensiveStressTest.php

# Performance
php BenchmarkTest.php
php PerformanceBenchmark.php

# Safety
php ConcurrencySafetyTest.php
php PanicRecoveryTest.php
php DeadlockDetector.php

# Lifecycle
php WorkerLifecycleTest.php
php GracefulShutdownTest.php
php SignalTest.php

# Long Running
php LongRunningTest.php 5          # 5 minutes
php MemoryLeakDetector.php 60      # 60 minutes
```

## Test Coverage

### Coroutine Safety
- ✓ Spawn cost and throughput
- ✓ Context switch overhead
- ✓ Nested coroutines
- ✓ Rapid spawn/destroy cycles
- ✓ Orphaned fiber cleanup
- ✓ State transitions
- ✓ Cancellation handling

### Channel Synchronization
- ✓ High volume messaging (100k+ msgs)
- ✓ Buffered and unbuffered channels
- ✓ Concurrent access (10+ producers/consumers)
- ✓ Message ordering
- ✓ Backpressure handling
- ✓ Channel latency
- ✓ Resource cleanup

### Event Loop Reliability
- ✓ Latency (avg, p50, p95, p99)
- ✓ Blocking detection
- ✓ Timer accuracy and precision
- ✓ Timer storm (1000+ timers)
- ✓ Long running stability

### Worker Lifecycle
- ✓ Graceful shutdown (SIGTERM/SIGINT)
- ✓ Signal handling
- ✓ Cleanup on exit
- ✓ Multiple signals (idempotent)
- ✓ Process forking

### Panic Recovery
- ✓ Unhandled exceptions
- ✓ Error isolation
- ✓ Process stability

### Memory Management
- ✓ Memory per coroutine (~2KB)
- ✓ Leak detection (10k+ iterations)
- ✓ Long running stability (hours)
- ✓ Memory pressure (large payloads)
- ✓ Resource cleanup

### Deadlock Detection
- ✓ Simple deadlock (2 channels)
- ✓ Circular deadlock (3+ channels)
- ✓ Channel deadlock (unbuffered)
- ✓ Nested deadlock
- ✓ Partial deadlock

### Concurrency Safety
- ✓ Race condition detection
- ✓ Atomic operations
- ✓ Shared state access
- ✓ Concurrent reads/writes
- ✓ Producer-consumer pattern

## Performance Targets

### Latency (all met ✓)
- Coroutine spawn: < 0.1ms
- Context switch: < 0.02ms
- Yield: < 0.01ms
- Channel ops: < 0.05ms
- Timer overhead: < 0.02ms
- Event loop tick: < 1ms

### Throughput (all met ✓)
- Coroutine spawns: > 10,000/s
- Context switches: > 50,000/s
- Channel messages: > 50,000/s

### Memory (all met ✓)
- Per coroutine: < 3KB
- Leak rate: < 1KB/s
- Peak usage: < 50MB (for 10k coroutines)

### Stability (all met ✓)
- Long running: > 1 hour without errors
- Memory stable: < 5% variance
- Error rate: 0%

## Requirements

- PHP 8.1+ (Fiber support)
- CLI SAPI
- PCNTL extension (for signal tests)
- POSIX extension (for signal tests)

## CI Integration

```yaml
# .github/workflows/runtime-tests.yml
name: Runtime Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: pcntl, posix
      - name: Quick Test
        run: cd tests/Runtime && ./quick_test.sh
      - name: Full Test Suite
        run: cd tests/Runtime && ./run_all_tests.sh
```

## Troubleshooting

### Test Failures

**"Runtime not available"**
- Ensure PHP 8.1+ with Fiber support
- Run in CLI mode: `php -v` should show "cli"

**"PCNTL extension required"**
- Install pcntl: `apt-get install php-pcntl` (Ubuntu/Debian)
- Signal tests require pcntl

**Timeout errors**
- Increase timeout in test files
- Check system load

**Memory errors**
- Increase PHP memory limit: `php -d memory_limit=512M test.php`

### Performance Issues

**Low throughput**
- Check CPU load
- Disable Xdebug
- Use opcache

**High latency**
- Check system load
- Reduce concurrent tests

## License

MIT
