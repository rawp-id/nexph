<?php
/**
 * Schedule configuration.
 * Define scheduled tasks using the Schedule API.
 */

use Runtime\Scheduler\Schedule;

$schedule = new Schedule();

// Clean up old logs every day at 2 AM
$schedule->daily('02:00', function() {
    echo "Cleaning up old logs...\n";
    // Cleanup logic here
})
->name('cleanup-logs')
->description('Remove logs older than 30 days');

// Send daily summary email at 9 AM
$schedule->daily('09:00', function() {
    echo "Sending daily summary email...\n";
    // Email logic here
})
->name('daily-summary')
->description('Send daily summary to administrators');

// Process pending webhooks every 5 minutes
$schedule->everyFiveMinutes(function() {
    echo "Processing pending webhooks...\n";
    // Webhook processing logic
})
->name('process-webhooks')
->description('Process queued webhook deliveries');

// Health check every minute
$schedule->everyMinute(function() {
    echo "Running health check...\n";
    // Health check logic
})
->name('health-check')
->description('Monitor system health');

// Generate weekly reports on Monday at 8 AM
$schedule->weekly(1, '08:00', function() {
    echo "Generating weekly reports...\n";
    // Report generation logic
})
->name('weekly-reports')
->description('Generate and send weekly reports');

// Backup database every 6 hours
$schedule->every(21600, function() {
    echo "Backing up database...\n";
    // Backup logic
})
->name('database-backup')
->description('Create database backup');

return $schedule;
