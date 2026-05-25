#!/bin/bash

echo "=== Memory Leak Detection ==="
echo "This will run for the specified duration (default: 60 minutes)"
echo ""

DURATION=${1:-60}
TESTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Duration: $DURATION minutes"
echo "Start: $(date)"
echo ""

php "$TESTS_DIR/MemoryLeakDetector.php" "$DURATION"
EXIT_CODE=$?

echo ""
echo "End: $(date)"

if [ $EXIT_CODE -eq 0 ]; then
    echo "✓ No memory leaks detected"
else
    echo "✗ Memory leak detected"
fi

exit $EXIT_CODE
