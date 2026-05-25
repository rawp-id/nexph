#!/bin/bash

# Master test runner for all chaos, persistence, and production tests

echo "=== Nexph Runtime & Queue Production Test Suite ==="
echo "Started: $(date)"
echo ""

TESTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FAILED=0
PASSED=0

run_test() {
    local test_file=$1
    local test_name=$2
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Running: $test_name"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    if php "$test_file"; then
        echo "✓ $test_name PASSED"
        ((PASSED++))
    else
        echo "✗ $test_name FAILED"
        ((FAILED++))
    fi
    
    echo ""
}

# Chaos Engineering Tests
run_test "$TESTS_DIR/ChaosTest.php" "Chaos Engineering"

# Persistence & Recovery Tests
run_test "$TESTS_DIR/PersistenceRecoveryTest.php" "Persistence & Recovery"

# Signal Handling Tests
run_test "$TESTS_DIR/SignalHandlingTest.php" "Signal Handling & Graceful Shutdown"

# Observability Tests
run_test "$TESTS_DIR/ObservabilityTest.php" "Observability & Metrics"

# Driver Failure Tests
run_test "$TESTS_DIR/DriverFailureTest.php" "Driver Failure & Degradation"

# Extended Soak Test (short version - 5 minutes)
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Running: Extended Soak Test (5 minutes)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if php "$TESTS_DIR/ExtendedSoakTest.php" 5; then
    echo "✓ Extended Soak Test PASSED"
    ((PASSED++))
else
    echo "✗ Extended Soak Test FAILED"
    ((FAILED++))
fi
echo ""

# Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "=== Test Suite Summary ==="
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Passed: $PASSED"
echo "Failed: $FAILED"
echo "Total:  $((PASSED + FAILED))"
echo "Completed: $(date)"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "✓ ALL TESTS PASSED"
    exit 0
else
    echo "✗ SOME TESTS FAILED"
    exit 1
fi
