#!/bin/bash
# Nexph Runtime Ecosystem Verification Script

echo "=========================================="
echo "  NEXPH RUNTIME ECOSYSTEM VERIFICATION"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
PASSED=0
FAILED=0

# Helper functions
pass() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

fail() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# Check PHP version
echo "1. Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if [[ $(php -r "echo version_compare(PHP_VERSION, '8.1.0', '>=') ? 1 : 0;") == "1" ]]; then
    pass "PHP version: $PHP_VERSION"
else
    fail "PHP version: $PHP_VERSION (requires 8.1+)"
fi
echo ""

# Check Fiber support
echo "2. Checking Fiber support..."
if php -r "exit(class_exists('Fiber') ? 0 : 1);" 2>/dev/null; then
    pass "Fiber support available"
else
    fail "Fiber support not available"
fi
echo ""

# Check CLI SAPI
echo "3. Checking PHP SAPI..."
if [[ $(php -r "echo PHP_SAPI;") == "cli" ]]; then
    pass "Running in CLI mode"
else
    fail "Not running in CLI mode"
fi
echo ""

# Check runtime files
echo "4. Checking runtime files..."
FILES=(
    "runtime/Runtime.php"
    "runtime/EventLoop.php"
    "runtime/Coroutine.php"
    "runtime/Channel.php"
    "runtime/Timer.php"
    "runtime/Observability/RuntimeMetrics.php"
    "runtime/Observability/HealthMonitor.php"
    "runtime/Observability/Dashboard.php"
    "runtime/Observability/Logger.php"
    "runtime/CLI/CommandRegistry.php"
    "runtime/Scheduler/Schedule.php"
    "runtime/Scheduler/ScheduledTask.php"
    "runtime/Supervisor/Supervisor.php"
    "runtime/Queue/Queue.php"
)

for file in "${FILES[@]}"; do
    if [[ -f "$file" ]]; then
        pass "$file"
    else
        fail "$file (missing)"
    fi
done
echo ""

# Check CLI commands
echo "5. Checking CLI commands..."
if [[ -x "bin/nexph" ]]; then
    pass "bin/nexph executable"
else
    fail "bin/nexph not executable"
fi
echo ""

# Check job handlers
echo "6. Checking job handlers..."
JOBS=(
    "app/Jobs/SendEmailJob.php"
    "app/Jobs/ProcessWebhookJob.php"
    "app/Jobs/SendNotificationJob.php"
    "app/Jobs/GenerateReportJob.php"
)

for job in "${JOBS[@]}"; do
    if [[ -f "$job" ]]; then
        pass "$job"
    else
        fail "$job (missing)"
    fi
done
echo ""

# Check examples
echo "7. Checking examples..."
EXAMPLES=(
    "examples/simple_workload_test.php"
    "examples/workload_test.php"
    "examples/supervisor_daemon.php"
    "examples/dashboard_live.php"
)

for example in "${EXAMPLES[@]}"; do
    if [[ -f "$example" ]]; then
        pass "$example"
    else
        fail "$example (missing)"
    fi
done
echo ""

# Check HTTP endpoints
echo "8. Checking HTTP endpoints..."
ENDPOINTS=(
    "public/observability.php"
    "public/metrics.php"
)

for endpoint in "${ENDPOINTS[@]}"; do
    if [[ -f "$endpoint" ]]; then
        pass "$endpoint"
    else
        fail "$endpoint (missing)"
    fi
done
echo ""

# Check documentation
echo "9. Checking documentation..."
DOCS=(
    "runtime/RUNTIME_ECOSYSTEM.md"
    "runtime/QUICKSTART.md"
    "runtime/IMPLEMENTATION_SUMMARY.md"
    "RUNTIME_COMPLETE.md"
)

for doc in "${DOCS[@]}"; do
    if [[ -f "$doc" ]]; then
        pass "$doc"
    else
        fail "$doc (missing)"
    fi
done
echo ""

# Test CLI help
echo "10. Testing CLI help..."
if php bin/nexph help > /dev/null 2>&1; then
    pass "CLI help command works"
else
    fail "CLI help command failed"
fi
echo ""

# Test runtime status
echo "11. Testing runtime status..."
if php bin/nexph runtime:status > /dev/null 2>&1; then
    pass "Runtime status command works"
else
    fail "Runtime status command failed"
fi
echo ""

# Test schedule list
echo "12. Testing schedule list..."
if php bin/nexph schedule:list > /dev/null 2>&1; then
    pass "Schedule list command works"
else
    fail "Schedule list command failed"
fi
echo ""

# Test autoloader
echo "13. Testing autoloader..."
if php -r "require 'runtime/autoload.php'; exit(class_exists('Runtime\\Runtime') ? 0 : 1);" 2>/dev/null; then
    pass "Runtime autoloader works"
else
    fail "Runtime autoloader failed"
fi

if php -r "require 'runtime/autoload.php'; exit(class_exists('App\\Jobs\\SendEmailJob') ? 0 : 1);" 2>/dev/null; then
    pass "App autoloader works"
else
    fail "App autoloader failed"
fi
echo ""

# Summary
echo "=========================================="
echo "  VERIFICATION SUMMARY"
echo "=========================================="
echo ""
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo ""

if [[ $FAILED -eq 0 ]]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Run: php examples/simple_workload_test.php"
    echo "  2. Run: php bin/nexph runtime:status --verbose"
    echo "  3. Run: php bin/nexph schedule:list"
    echo "  4. Read: runtime/QUICKSTART.md"
    echo ""
    exit 0
else
    echo -e "${RED}✗ Some checks failed${NC}"
    echo ""
    exit 1
fi
