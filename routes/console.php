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
Schedule::command('attendance:auto-checkout')->everyThirtyMinutes();

// Process Daily Absences (at the end of the day)
Schedule::command('attendance:process-absences')->dailyAt('23:55');
