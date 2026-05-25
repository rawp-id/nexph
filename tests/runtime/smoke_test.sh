#!/bin/bash

# Quick smoke test for production readiness

echo "=== Quick Production Smoke Test ==="
echo ""

TESTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "1. Testing chaos recovery..."
php "$TESTS_DIR/ChaosTest.php" | grep -E "(PASS|FAIL)" | head -5

echo ""
echo "2. Testing persistence..."
php "$TESTS_DIR/PersistenceRecoveryTest.php" | grep -E "(PASS|FAIL)" | head -5

echo ""
echo "3. Testing observability..."
php "$TESTS_DIR/ObservabilityTest.php" | grep -E "(PASS|FAIL)" | head -5

echo ""
echo "✓ Smoke test complete"
