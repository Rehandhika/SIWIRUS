<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\AuditLog;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Database Backup (Requires spatie/laravel-backup)
// Make sure to configure config/backup.php
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');

// Prune Models (Requires Prunable trait on models, or manual pruning below)
// Assuming models implement MassPrunable or Prunable
// Schedule::command('model:prune', [
//     '--model' => [App\Models\AuditLog::class],
// ])->daily()->at('02:00');

// Manual Cleanup for Non-Prunable Models
Schedule::call(function () {
    // Keep logs for 6 months
    ActivityLog::where('created_at', '<', now()->subMonths(6))->delete();
    
    // Keep notifications for 3 months
    Notification::where('created_at', '<', now()->subMonths(3))->delete();

    // Keep audit logs for 1 year
    AuditLog::where('created_at', '<', now()->subYear())->delete();
})->daily()->at('02:30')->name('cleanup-old-data');

// Clean Livewire Temp Files
Schedule::command('livewire:configure-s3-upload-cleanup')->daily()->at('03:00'); // If using S3
// For local, Laravel handles this via garbage collection, but explicit cleanup is good.
// No built-in command for local yet without custom implementation.

// Monitor Queue
Schedule::command('queue:monitor default:100')->everyFiveMinutes();

// Auto Checkout Attendance (3 hours buffer after session ends)
// Sesi 1 ends 10:00 -> Check at 13:05
// Sesi 2 ends 12:50 -> Check at 15:55
// Sesi 3 ends 16:00 -> Check at 19:05
Schedule::command('attendance:auto-checkout')->dailyAt('13:05');
Schedule::command('attendance:auto-checkout')->dailyAt('15:55');
Schedule::command('attendance:auto-checkout')->dailyAt('19:05');

// ============================================================
// ABSENCE & PENALTY PROCESSING
// ============================================================
// Strategy: Multiple layers of checking to ensure no missed absences.
// All commands are idempotent — safe to run repeatedly.
//
// Layer 1 (Primary): Check every 15 min during work hours (Mon-Thu)
//   Catches absences as soon as each session ends.
Schedule::command('attendance:check-late-absences')
    ->everyFifteenMinutes()
    ->weekdays()
    ->between('10:00', '17:00')
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Layer 2 (Same-day fallback): Process today's ended sessions every 30 min
//   In case check-late-absences misses something.
Schedule::command('attendance:process-absences', ['today'])
    ->everyThirtyMinutes()
    ->weekdays()
    ->between('10:30', '17:30')
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Layer 3 (End-of-day fallback): Process yesterday's absences at midnight
//   Final safety net for anything that slipped through during the day.
Schedule::command('attendance:process-absences')
    ->dailyAt('00:05')
    ->appendOutputTo(storage_path('logs/scheduler.log'));
