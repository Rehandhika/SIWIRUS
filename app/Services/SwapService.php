<?php

namespace App\Services;

use App\Models\ScheduleAssignment;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SwapService
{
    /**
     * Create swap request
     */
    public function createSwapRequest(
        ScheduleAssignment $requesterAssignment,
        ScheduleAssignment $targetAssignment,
        string $reason
    ): SwapRequest {
        // Validate
        $this->validateSwapRequest($requesterAssignment, $targetAssignment);

        $swapRequest = SwapRequest::create([
            'user_id' => $requesterAssignment->user_id,
            'target_id' => $targetAssignment->user_id,
            'original_assignment_id' => $requesterAssignment->id,
            'target_assignment_id' => $targetAssignment->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        // Notify target user
        NotificationService::send(
            $targetAssignment->user,
            'swap_request_received',
            'Permintaan Tukar Jadwal',
            "{$requesterAssignment->user->name} ingin menukar jadwal dengan Anda. {$requesterAssignment->day_label} {$requesterAssignment->date->format('d M')} Sesi {$requesterAssignment->session} ↔ {$targetAssignment->day_label} {$targetAssignment->date->format('d M')} Sesi {$targetAssignment->session}",
            ['swap_request_id' => $swapRequest->id],
            route('swap.my-requests')
        );

        return $swapRequest;
    }

    /**
     * Validate swap request
     */
    private function validateSwapRequest(
        ScheduleAssignment $requesterAssignment,
        ScheduleAssignment $targetAssignment
    ): void {
        // Check both are scheduled
        if (! $requesterAssignment->isScheduled() || ! $targetAssignment->isScheduled()) {
            throw new \Exception('Kedua jadwal harus berstatus "scheduled" (terjadwal).');
        }

        // Check minimum notice time (24 hours)
        $minNoticeHours = (int) setting('swap.min_notice_hours', 24);
        if ($requesterAssignment->date->diffInHours(now()) < $minNoticeHours) {
            throw new \Exception("Permintaan tukar jadwal harus diajukan minimal {$minNoticeHours} jam sebelum jadwal dimulai.");
        }

        // Check if requester has another assignment at the target time (Double Booking Check)
        $conflictingAssignment = ScheduleAssignment::where('user_id', $requesterAssignment->user_id)
            ->where('date', $targetAssignment->date)
            ->where('session', $targetAssignment->session)
            ->where('id', '!=', $requesterAssignment->id)
            ->exists();

        if ($conflictingAssignment) {
            throw new \Exception('Anda sudah memiliki jadwal lain pada waktu target tukar jadwal ini.');
        }
        $maxPerMonth = (int) setting('swap.max_per_month', 2);
        $thisMonthCount = SwapRequest::where('user_id', $requesterAssignment->user_id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['admin_approved'])
            ->count();

        if ($thisMonthCount >= $maxPerMonth) {
            throw new \Exception("Batas maksimal {$maxPerMonth} kali tukar jadwal per bulan telah tercapai.");
        }
    }

    /**
     * Target user responds to swap request
     */
    public function targetRespond(SwapRequest $swapRequest, bool $approved, ?string $response = null): void
    {
        $swapRequest->update([
            'status' => $approved ? 'target_approved' : 'target_rejected',
            'target_response' => $response,
            'target_responded_at' => now(),
        ]);

        if ($approved) {
            // Notify admins for final approval
            $admins = User::role(['Super Admin', 'Ketua', 'Wakil Ketua', 'BPH'])->get();
            foreach ($admins as $admin) {
                NotificationService::send(
                    $admin,
                    'swap_request_target_approved',
                    'Persetujuan Tukar Jadwal',
                    "Permintaan tukar jadwal antara {$swapRequest->requester->name} dan {$swapRequest->target->name} telah disetujui target. Menunggu approval admin.",
                    ['swap_request_id' => $swapRequest->id],
                    route('swap.approvals')
                );
            }
        } else {
            // Notify requester about rejection
            NotificationService::send(
                $swapRequest->requester,
                'swap_request_target_rejected',
                'Permintaan Tukar Ditolak',
                "{$swapRequest->target->name} menolak permintaan tukar jadwal Anda. Alasan: {$response}",
                ['swap_request_id' => $swapRequest->id]
            );
        }
    }

    /**
     * Admin approves/rejects swap request
     */
    public function adminRespond(
        SwapRequest $swapRequest,
        bool $approved,
        ?string $response = null
    ): void {
        try {
            DB::beginTransaction();

            $swapRequest->update([
                'status' => $approved ? 'admin_approved' : 'admin_rejected',
                'admin_response' => $response,
                'admin_responded_by' => auth()->id(),
                'admin_responded_at' => now(),
                'completed_at' => now(),
            ]);

            if ($approved) {
                // Execute the swap
                $this->executeSwap($swapRequest);

                // Notify both users
                NotificationService::send(
                    $swapRequest->requester,
                    'swap_request_approved',
                    'Tukar Jadwal Disetujui',
                    "Permintaan tukar jadwal Anda dengan {$swapRequest->target->name} telah disetujui admin.",
                    ['swap_request_id' => $swapRequest->id]
                );

                NotificationService::send(
                    $swapRequest->target,
                    'swap_request_approved',
                    'Tukar Jadwal Disetujui',
                    "Permintaan tukar jadwal dengan {$swapRequest->requester->name} telah disetujui admin.",
                    ['swap_request_id' => $swapRequest->id]
                );
            } else {
                // Notify requester about admin rejection
                NotificationService::send(
                    $swapRequest->requester,
                    'swap_request_admin_rejected',
                    'Tukar Jadwal Ditolak Admin',
                    "Admin menolak permintaan tukar jadwal Anda. Alasan: {$response}",
                    ['swap_request_id' => $swapRequest->id]
                );
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Execute the schedule swap
     */
    private function executeSwap(SwapRequest $swapRequest): void
    {
        $requesterAssignment = $swapRequest->requesterAssignment;
        $targetAssignment = $swapRequest->targetAssignment;

        // Swap user IDs
        $tempUserId = $requesterAssignment->user_id;

        $requesterAssignment->update([
            'user_id' => $targetAssignment->user_id,
            'status' => 'swapped',
            'swapped_to_user_id' => $targetAssignment->user_id,
        ]);

        $targetAssignment->update([
            'user_id' => $tempUserId,
            'status' => 'swapped',
            'swapped_to_user_id' => $tempUserId,
        ]);
    }
}
