<?php

namespace App\Services;

use App\Models\Penalty;
use App\Models\PenaltyType;
use App\Models\User;
use Carbon\Carbon;

class PenaltyService
{
    /**
     * Create penalty for user with automatic threshold checking
     */
    public function createPenalty(
        int $userId,
        string $penaltyTypeCode,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?Carbon $date = null
    ): Penalty {
        $user = User::findOrFail($userId);

        $penaltyType = PenaltyType::where('code', $penaltyTypeCode)
            ->where('is_active', true)
            ->firstOrFail();

        // Check for duplicate penalty reference
        if ($referenceType && $referenceId) {
            $existingPenalty = Penalty::where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->first();

            if ($existingPenalty) {
                throw new \Exception("Penalti sudah ada untuk referensi ini ({$referenceType}:{$referenceId})");
            }
        }

        $penalty = Penalty::create([
            'user_id' => $user->id,
            'penalty_type_id' => $penaltyType->id,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'points' => $penaltyType->points,
            'description' => $description,
            'date' => $date ?? now(),
            'status' => 'active',
        ]);

        // Notify user
        NotificationService::send(
            $user,
            'penalty_assigned',
            'Penalti Baru',
            "Anda mendapat penalti: {$penaltyType->name} ({$penaltyType->points} poin). {$description}",
            ['penalty_id' => $penalty->id],
            route('admin.my-penalties')
        );

        // Log activity
        ActivityLogService::logPenaltyCreated($user->name, $penaltyType->points, $penaltyType->name);

        // Check if threshold reached (automatic threshold checking)
        $this->checkThresholds($userId);

        return $penalty;
    }

    /**
     * Get user total active penalty points
     * Excludes penalties with status 'dismissed' and 'expired'
     */
    public function getUserTotalPoints(int $userId): int
    {
        return Penalty::where('user_id', $userId)
            ->whereNotIn('status', ['dismissed', 'expired'])
            ->sum('points');
    }

    /**
     * Check penalty thresholds and take action
     * Warning at 20 points, approaching critical at 40 points (80%), critical at 50 points
     */
    public function checkThresholds(int $userId): ?string
    {
        $user = User::findOrFail($userId);
        $totalPoints = $this->getUserTotalPoints($userId);

        $warningThreshold = 20;
        $approachingThreshold = 40; // 80% of critical threshold
        $criticalThreshold = 50;

        if ($totalPoints >= $criticalThreshold) {
            // Critical warning notification - notify user and admins
            NotificationService::send(
                $user,
                'penalty_critical',
                'Peringatan Kritis Penalti',
                "Total poin penalti Anda: {$totalPoints}. Anda telah mencapai batas kritis. Harap segera hubungi administrator.",
                null,
                route('admin.my-penalties')
            );

            // Notify admins about critical threshold
            $admins = User::role(['Super Admin', 'Ketua', 'Wakil Ketua'])->get();
            foreach ($admins as $admin) {
                NotificationService::send(
                    $admin,
                    'user_penalty_critical',
                    'User Mencapai Batas Kritis Penalti',
                    "{$user->name} telah mencapai {$totalPoints} poin penalti (batas kritis: {$criticalThreshold} poin).",
                    ['user_id' => $user->id],
                    route('admin.users.index')
                );
            }

            return 'critical';

        } elseif ($totalPoints >= $approachingThreshold) {
            // Approaching critical threshold (80%) - stronger warning
            NotificationService::send(
                $user,
                'penalty_approaching_critical',
                'Peringatan: Mendekati Batas Kritis',
                "Total poin penalti Anda: {$totalPoints}. Anda mendekati batas kritis ({$criticalThreshold} poin). Harap segera perbaiki kedisiplinan Anda.",
                null,
                route('admin.my-penalties')
            );

            return 'approaching_critical';

        } elseif ($totalPoints >= $warningThreshold) {
            // Warning notification
            NotificationService::send(
                $user,
                'penalty_warning',
                'Peringatan Penalti',
                "Total poin penalti Anda: {$totalPoints}. Batas kritis: {$criticalThreshold} poin. Harap perhatikan kedisiplinan Anda.",
                null,
                route('admin.my-penalties')
            );

            return 'warning';
        }

        return null;
    }

    /**
     * Process penalty appeal
     */
    public function submitAppeal(Penalty $penalty, string $reason): bool
    {
        $penalty->update([
            'status' => 'appealed',
            'appeal_reason' => $reason,
            'appeal_status' => 'pending',
            'appealed_at' => now(),
        ]);

        // Notify admins
        $admins = User::role(['Super Admin', 'Ketua', 'Wakil Ketua'])->get();
        foreach ($admins as $admin) {
            NotificationService::send(
                $admin,
                'penalty_appeal',
                'Banding Penalti',
                "{$penalty->user->name} mengajukan banding untuk penalti {$penalty->penaltyType->name}.",
                ['penalty_id' => $penalty->id],
                route('admin.reports.penalties')
            );
        }

        return true;
    }

    /**
     * Admin review penalty appeal
     */
    public function reviewAppeal(
        Penalty $penalty,
        bool $approved,
        string $notes,
        int $reviewerId
    ): bool {
        if ($approved) {
            // Approved: dismiss penalty and recalculate total points
            $penalty->update([
                'status' => 'dismissed',
                'appeal_status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            // Sync with related attendance if exists
            if ($penalty->reference_type === 'attendance' && $penalty->reference_id) {
                $attendance = \App\Models\Attendance::find($penalty->reference_id);
                if ($attendance) {
                    $attendance->update(['status' => 'excused']);
                    
                    // Also sync with schedule assignment
                    if ($attendance->schedule_assignment_id) {
                        $attendance->scheduleAssignment->update(['status' => 'excused']);
                    }
                }
            }

            // Recalculate and check thresholds
            $this->checkThresholds($penalty->user_id);

            NotificationService::send(
                $penalty->user,
                'appeal_approved',
                'Banding Disetujui',
                'Banding penalti Anda telah disetujui. Penalti telah dibatalkan.',
                ['penalty_id' => $penalty->id],
                route('admin.my-penalties')
            );
        } else {
            // Rejected: keep penalty active
            $penalty->update([
                'status' => 'active',
                'appeal_status' => 'rejected',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            NotificationService::send(
                $penalty->user,
                'appeal_rejected',
                'Banding Ditolak',
                'Banding penalti Anda telah ditolak. Penalti tetap aktif.',
                ['penalty_id' => $penalty->id],
                route('admin.my-penalties')
            );
        }

        return true;
    }
}
