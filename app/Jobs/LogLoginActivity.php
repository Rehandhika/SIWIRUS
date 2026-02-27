<?php

namespace App\Jobs;

use App\Models\LoginHistory;
use App\Services\ActivityLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogLoginActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public int $userId,
        public string $ipAddress,
        public string $userAgent,
        public string $status,
        public ?string $failureReason = null
    ) {}

    public function handle(): void
    {
        try {
            // Create login history
            LoginHistory::create([
                'user_id' => $this->status === 'success' ? $this->userId : null,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'logged_in_at' => now(),
                'status' => $this->status,
                'failure_reason' => $this->failureReason,
            ]);

            // Create activity log for successful login using ActivityLogService
            if ($this->status === 'success') {
                ActivityLogService::logLogin();
            }
        } catch (\Exception $e) {
            Log::error('Failed to log login activity: '.$e->getMessage());
        }
    }
}
