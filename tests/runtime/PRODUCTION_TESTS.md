# Production-Grade Chaos & Reliability Testing Suite

## Overview

Comprehensive test suite for Nexph's runtime and queue system covering chaos engineering, persistence, failure recovery, signal handling, observability, and extended soak testing.

## Test Suites

### 1. Chaos Engineering (`ChaosTest.php`)
Tests system behavior under extreme conditions:
- Worker crash recovery
- Fatal exception handling
- Memory pressure handling
- Large payload processing (10K+ items)
- Job timeout enforcement
- Retry storm prevention
- Queue starvation detection
- Concurrent worker crash isolation
- Duplicate job prevention
- Partial failure recovery

### 2. Persistence & Recovery (`PersistenceRecoveryTest.php`)
Validates data durability across restarts:
- File driver persistence across instances
- File driver recovery after crash
- Database driver persistence
- Database driver recovery after crash
- Jobs not lost during restart
- Jobs not duplicated during restart
- Jobs not corrupted during restart
- Dead letter queue persistence
- Crash during processing recovery
- Multiple restart cycles

### 3. Signal Handling (`SignalHandlingTest.php`)
Tests graceful shutdown and signal propagation:
- SIGTERM handling
- SIGINT handling
- Graceful shutdown
- Job completion before exit
- Multiple signals handling
- Signal during job processing
- Forked worker signal handling
- Signal propagation to child processes

### 4. Observability (`ObservabilityTest.php`)
Validates metrics and monitoring:
- Queue depth tracking
- Retry rate metrics
- Active jobs tracking
- Failed jobs metrics
- Job throughput measurement
- Memory growth tracking
- Worker health metrics
- Average duration tracking
- Metrics accuracy validation
- Extended soak metrics (30s+)

### 5. Driver Failure & Degradation (`DriverFailureTest.php`)
Tests driver resilience and safe degradation:
- Redis disconnect handling
- Database connection loss
- File system full simulation
- File permission denied
- Corrupted queue data handling
- Driver recovery after failure
- Fallback to memory driver
- Partial driver failure handling
- Concurrent driver failures
- Stateless core isolation from driver failures

### 6. Extended Soak Test (`ExtendedSoakTest.php`)
Long-running production reliability test (configurable duration):
- Long-running worker stability
- Memory stability over time
- Throughput consistency
- Error recovery over time
- Queue fairness under load
- Scheduler responsiveness

## Running Tests

### Quick Smoke Test (5 minutes)
```bash
./smoke_test.sh
```

### Full Production Test Suite
```bash
./run_production_tests.sh
```

### Individual Tests
```bash
php ChaosTest.php
php PersistenceRecoveryTest.php
php SignalHandlingTest.php
php ObservabilityTest.php
php DriverFailureTest.php
php ExtendedSoakTest.php [duration_minutes]
```

### Extended Soak Test (custom duration)
```bash
# 1 hour
php ExtendedSoakTest.php 60

# 6 hours
php ExtendedSoakTest.php 360

# 24 hours
php ExtendedSoakTest.php 1440
```

## Requirements

- PHP 8.1+ with Fiber support
- CLI mode
- pcntl extension (for signal handling and forking tests)
- Optional: Redis extension (for Redis driver tests)
- Optional: PDO SQLite (for database driver tests)

## Test Results Format

Each test outputs:
- ✓ PASS - Test passed with details
- ✗ FAIL - Test failed with reason
- ⊘ SKIP - Test skipped (missing requirements)

Final summary includes:
- Total passed/failed counts
- Overall status
- Detailed metrics (for soak tests)

## Production Readiness Criteria

All tests should pass with:
- Zero worker crashes
- Zero data loss
- Zero data corruption
- Graceful signal handling
- Memory growth < 1MB per 1000 jobs
- Throughput variance < 50%
- Error recovery rate > 80%
- Queue fairness score > 70%

## Continuous Integration

Add to CI pipeline:
```yaml
test:
  script:
    - cd tests/Runtime
    - ./run_production_tests.sh
```

For nightly builds, run extended soak:
```yaml
soak:
  script:
    - cd tests/Runtime
    - php ExtendedSoakTest.php 360  # 6 hours
```

## Metrics Collected

### Memory Metrics
- Memory usage snapshots
- Average growth per iteration
- Peak memory usage
- Memory stability over time

### Performance Metrics
- Job throughput (jobs/sec)
- Average job duration
- P95 response time
- Scheduler responsiveness

### Reliability Metrics
- Retry rate
- Failure rate
- Recovery rate
- Dead letter queue depth

### Worker Health
- Active worker count
- Jobs processed per worker
- Worker uptime
- Crash recovery time

## Troubleshooting

### Tests Skipped
Some tests require specific extensions:
- Signal tests: pcntl extension
- Redis tests: redis extension
- Fork tests: pcntl_fork support

### Tests Failing
1. Check system resources (memory, disk space)
2. Verify PHP version and extensions
3. Check file permissions
4. Review error logs
5. Run individual tests for detailed output

### Performance Issues
- Reduce worker count
- Decrease job payload size
- Increase poll interval
- Check system load

## Architecture Philosophy

These tests validate Nexph's core principles:
- **Stateless-first**: Core remains stable even when drivers fail
- **Adaptive runtime**: Graceful degradation when features unavailable
- **Production-safe**: All failures isolated and recoverable
- **Lightweight**: Minimal overhead, maximum reliability

## Future Enhancements

Planned additions:
- Network partition simulation
- Clock skew testing
- Distributed worker coordination
- Multi-node queue testing
- APCu driver tests
- Custom driver validation framework
