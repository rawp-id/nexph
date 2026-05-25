<?php
namespace App\Jobs;

use Core\Queue\JobHandler;

/**
 * Example: Generate report job.
 * Real-world workload for testing CPU-intensive operations.
 */
class GenerateReportJob extends JobHandler {
    public function handle(array $payload, ?callable $progress = null): void {
        $reportType = $payload['type'] ?? 'sales';
        $startDate = $payload['start_date'] ?? date('Y-m-01');
        $endDate = $payload['end_date'] ?? date('Y-m-d');
        
        $this->log("Generating {$reportType} report from {$startDate} to {$endDate}");
        
        // Simulate data fetching
        $this->log("Fetching data...");
        $data = $this->fetchData($reportType, $startDate, $endDate);
        
        // Simulate data processing
        $this->log("Processing data...");
        $processed = $this->processData($data);
        
        // Simulate report generation
        $this->log("Generating report...");
        $report = $this->generateReport($processed);
        
        $this->log("Report generated successfully");
    }
    
    private function fetchData(string $type, string $start, string $end): array {
        // Simulate database queries
        usleep(random_int(500000, 1000000)); // 500ms-1s
        
        // Generate fake data
        $count = random_int(100, 1000);
        $data = [];
        
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'id' => $i,
                'value' => random_int(100, 10000),
                'date' => date('Y-m-d', strtotime($start) + random_int(0, 86400 * 30)),
            ];
        }
        
        return $data;
    }
    
    private function processData(array $data): array {
        // Simulate CPU-intensive processing
        usleep(random_int(1000000, 2000000)); // 1-2s
        
        $processed = [
            'total' => array_sum(array_column($data, 'value')),
            'average' => count($data) > 0 ? array_sum(array_column($data, 'value')) / count($data) : 0,
            'count' => count($data),
        ];
        
        return $processed;
    }
    
    private function generateReport(array $data): string {
        // Simulate report generation
        usleep(random_int(500000, 1000000)); // 500ms-1s
        
        $report = "REPORT SUMMARY\n";
        $report .= str_repeat('=', 50) . "\n";
        $report .= "Total: {$data['total']}\n";
        $report .= "Average: " . number_format($data['average'], 2) . "\n";
        $report .= "Count: {$data['count']}\n";
        
        return $report;
    }
    
    private function log(string $message): void {
        echo "[GenerateReportJob] {$message}\n";
    }
}
