#!/bin/bash
# Comprehensive performance benchmark for Nexph

echo "=== NEXPH PERFORMANCE BENCHMARK ==="
echo "Date: $(date)"
echo ""

# Check if server is running
if ! curl -s http://localhost:8000/api/task > /dev/null 2>&1; then
    echo "ERROR: Server not running on localhost:8000"
    echo "Start server with: ./serve"
    exit 1
fi

echo "Server is running. Starting benchmarks..."
echo ""

# Create results directory
mkdir -p benchmark_results
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RESULTS_DIR="benchmark_results/${TIMESTAMP}"
mkdir -p "$RESULTS_DIR"

# Benchmark 1: Static route (no ORM)
echo "=== Benchmark 1: Static Route (Control) ==="
echo "Testing: GET /api (manifest endpoint)"
ab -n 1000 -c 10 -g "${RESULTS_DIR}/static.tsv" http://localhost:8000/api 2>&1 | tee "${RESULTS_DIR}/static.txt"
echo ""

# Benchmark 2: ORM List endpoint
echo "=== Benchmark 2: ORM List Endpoint ==="
echo "Testing: GET /api/task"
ab -n 1000 -c 10 -g "${RESULTS_DIR}/orm_list.tsv" http://localhost:8000/api/task 2>&1 | tee "${RESULTS_DIR}/orm_list.txt"
echo ""

# Benchmark 3: ORM Detail endpoint
echo "=== Benchmark 3: ORM Detail Endpoint ==="
echo "Testing: GET /api/task/12"
ab -n 1000 -c 10 -g "${RESULTS_DIR}/orm_detail.tsv" http://localhost:8000/api/task/12 2>&1 | tee "${RESULTS_DIR}/orm_detail.txt"
echo ""

# Benchmark 4: Higher concurrency
echo "=== Benchmark 4: High Concurrency Test ==="
echo "Testing: GET /api/task (50 concurrent)"
ab -n 1000 -c 50 -g "${RESULTS_DIR}/high_concurrency.tsv" http://localhost:8000/api/task 2>&1 | tee "${RESULTS_DIR}/high_concurrency.txt"
echo ""

# Extract key metrics
echo "=== SUMMARY ==="
echo ""

extract_metrics() {
    local file=$1
    local name=$2
    echo "[$name]"
    grep "Requests per second:" "$file" | head -1
    grep "Time per request:" "$file" | head -2
    grep "Transfer rate:" "$file" | head -1
    echo ""
}

extract_metrics "${RESULTS_DIR}/static.txt" "Static Route"
extract_metrics "${RESULTS_DIR}/orm_list.txt" "ORM List"
extract_metrics "${RESULTS_DIR}/orm_detail.txt" "ORM Detail"
extract_metrics "${RESULTS_DIR}/high_concurrency.txt" "High Concurrency"

echo "Results saved to: $RESULTS_DIR"
echo ""
echo "=== DETAILED ANALYSIS ==="

# Parse and compare
parse_rps() {
    grep "Requests per second:" "$1" | awk '{print $4}'
}

parse_latency() {
    grep "Time per request:" "$1" | head -1 | awk '{print $4}'
}

STATIC_RPS=$(parse_rps "${RESULTS_DIR}/static.txt")
ORM_RPS=$(parse_rps "${RESULTS_DIR}/orm_list.txt")
STATIC_LAT=$(parse_latency "${RESULTS_DIR}/static.txt")
ORM_LAT=$(parse_latency "${RESULTS_DIR}/orm_list.txt")

echo "Static Route:    ${STATIC_RPS} req/sec, ${STATIC_LAT}ms latency"
echo "ORM List Route:  ${ORM_RPS} req/sec, ${ORM_LAT}ms latency"

if [ ! -z "$STATIC_RPS" ] && [ ! -z "$ORM_RPS" ]; then
    SLOWDOWN=$(echo "scale=2; $STATIC_RPS / $ORM_RPS" | bc)
    echo ""
    echo "Performance Impact: ${SLOWDOWN}x slowdown with ORM"
fi
