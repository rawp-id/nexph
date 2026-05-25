#!/bin/bash

echo "=== Quick Runtime Validation ==="
echo ""

TESTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "1. Integration Test (basic functionality)..."
php "$TESTS_DIR/IntegrationTest.php" || exit 1
echo ""

echo "2. Stress Test (10k coroutines, 100k messages)..."
php "$TESTS_DIR/StressTest.php" || exit 1
echo ""

echo "3. Concurrency Safety..."
php "$TESTS_DIR/ConcurrencySafetyTest.php" || exit 1
echo ""

echo "✓ Quick validation passed"
echo "Run './run_all_tests.sh' for comprehensive testing"
