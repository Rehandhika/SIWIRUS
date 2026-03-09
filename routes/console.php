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

// Aggressively check for missed check-ins right after shift ends
// Sesi 1 ends 10:00 -> Check at 10:05
// Sesi 2 ends 12:50 -> Check at 12:55
// Sesi 3 ends 16:00 -> Check at 16:05
Schedule::command('attendance:check-late-absences')->dailyAt('10:05');
Schedule::command('attendance:check-late-absences')->dailyAt('12:55');
Schedule::command('attendance:check-late-absences')->dailyAt('16:05');

// Process Daily Absences (fallback for any missed by the aggressive checker)
Schedule::command('attendance:process-absences')->dailyAt('00:05');
