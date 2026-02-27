<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clean {--days=180 : The number of days of logs to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean activity and audit logs older than a specified number of days (default 6 months)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $date = Carbon::now()->subDays($days);

        $this->info("Membersihkan log yang lebih lama dari: " . $date->toDateTimeString());

        // Clean Activity Logs
        $activityCount = ActivityLog::where('created_at', '<', $date)->delete();
        $this->info("Berhasil menghapus $activityCount baris ActivityLog.");

        // Clean Audit Logs
        $auditCount = AuditLog::where('created_at', '<', $date)->delete();
        $this->info("Berhasil menghapus $auditCount baris AuditLog.");

        $this->info("Pembersihan log selesai!");

        return Command::SUCCESS;
    }
}
