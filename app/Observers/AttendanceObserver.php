<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\StoreStatusService;
use Illuminate\Support\Facades\Log;

class AttendanceObserver
{
    public function __construct(
        protected StoreStatusService $storeStatusService
    ) {}

    /**
     * Handle the Attendance "created" event.
     * Triggered when a staff member checks in.
     */
    public function created(Attendance $attendance): void
    {
        Log::info('Attendance CHECK-IN', [
            'user' => $attendance->user->name,
            'time' => $attendance->check_in?->toDateTimeString(),
            'date' => $attendance->date?->toDateString(),
        ]);

        $this->storeStatusService->forceUpdate();
    }

    /**
     * Handle the Attendance "updated" event.
     * Triggered when a staff member checks out.
     */
    public function updated(Attendance $attendance): void
    {
        if ($attendance->wasChanged('check_out') && $attendance->check_out) {
            Log::info('Attendance CHECK-OUT', [
                'user' => $attendance->user->name,
                'time' => $attendance->check_out->toDateTimeString(),
                'date' => $attendance->date?->toDateString(),
            ]);

            $this->storeStatusService->forceUpdate();
        }
    }

    /**
     * Handle the Attendance "deleted" event.
     * Clean up associated penalties and revert schedule status.
     */
    public function deleted(Attendance $attendance): void
    {
        // Remove associated penalties
        $attendance->penalties()->delete();

        // Revert schedule assignment to 'scheduled' if it was linked
        if ($attendance->schedule_assignment_id) {
            $attendance->scheduleAssignment->update(['status' => 'scheduled']);
        }
        
        Log::info('Attendance DELETED - Associated penalties cleaned up and schedule reverted', [
            'user' => $attendance->user->name,
            'date' => $attendance->date?->toDateString(),
        ]);
    }
}
