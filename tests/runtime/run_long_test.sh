#!/bin/bash

echo "=== Long Running Stability Test ==="
echo "This will run for the specified duration (default: 5 minutes)"
echo ""

DURATION=${1:-5}
TESTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Duration: $DURATION minutes"
echo "Start: $(date)"
echo ""

php "$TESTS_DIR/LongRunningTest.php" "$DURATION"
EXIT_CODE=$?

echo ""
echo "End: $(date)"

if [ $EXIT_CODE -eq 0 ]; then
    echo "✓ Long running test passed"
else
    echo "✗ Long running test failed"
fi

exit $EXIT_CODE
