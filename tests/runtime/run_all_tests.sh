#!/bin/bash

echo "=== Nexph Runtime Test Suite ==="
echo "Date: $(date)"
echo "PHP: $(php -v | head -n 1)"
echo ""

TESTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FAILED=0
PASSED=0

run_test() {
    local test_file=$1
    local test_name=$2
    
    echo "Running: $test_name"
    if php "$test_file"; then
        ((PASSED++))
        echo ""
    else
        ((FAILED++))
        echo "✗ $test_name FAILED"
        echo ""
    fi
}

echo "=== Core Tests ==="
run_test "$TESTS_DIR/IntegrationTest.php" "Integration Test"

echo "=== Stress Tests ==="
run_test "$TESTS_DIR/StressTest.php" "Stress Test"
run_test "$TESTS_DIR/ComprehensiveStressTest.php" "Comprehensive Stress Test"

echo "=== Performance Tests ==="
run_test "$TESTS_DIR/BenchmarkTest.php" "Benchmark Test"
run_test "$TESTS_DIR/PerformanceBenchmark.php" "Performance Benchmark"

echo "=== Safety Tests ==="
run_test "$TESTS_DIR/ConcurrencySafetyTest.php" "Concurrency Safety Test"
run_test "$TESTS_DIR/PanicRecoveryTest.php" "Panic Recovery Test"
run_test "$TESTS_DIR/DeadlockDetector.php" "Deadlock Detector"

echo "=== Lifecycle Tests ==="
run_test "$TESTS_DIR/WorkerLifecycleTest.php" "Worker Lifecycle Test"
run_test "$TESTS_DIR/GracefulShutdownTest.php" "Graceful Shutdown Test"
run_test "$TESTS_DIR/SignalTest.php" "Signal Test"

echo "=== Summary ==="
echo "Passed: $PASSED"
echo "Failed: $FAILED"
echo "Total: $((PASSED + FAILED))"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo "✓ All tests passed"
    exit 0
else
    echo ""
    echo "✗ Some tests failed"
    exit 1
fi
