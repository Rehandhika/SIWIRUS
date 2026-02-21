<?php

namespace App\Repositories;

use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class SwapRepository
{
    /**
     * Get swap requests for user
     */
    public function getUserSwapRequests(int $userId, ?string $status = null): Collection
    {
        $query = SwapRequest::where('user_id', $userId)
            ->with([
                'requester:id,name,nim',
                'target:id,name,nim',
                'requesterAssignment.schedule',
                'targetAssignment.schedule',
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get swap requests where user is target
     */
    public function getReceivedSwapRequests(int $userId, ?string $status = null): Collection
    {
        $query = SwapRequest::where('target_id', $userId)
            ->with([
                'requester:id,name,nim',
                'target:id,name,nim',
                'requesterAssignment.schedule',
                'targetAssignment.schedule',
            ]);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all swap requests (for admin)
     */
    public function getAllSwapRequests(?string $status = null): Collection
    {
        $query = SwapRequest::with([
            'requester:id,name,nim',
            'target:id,name,nim',
            'requesterAssignment.schedule',
            'targetAssignment.schedule',
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create swap request
     */
    public function create(array $data): SwapRequest
    {
        return SwapRequest::create($data);
    }

    /**
     * Update swap request
     */
    public function update(int $id, array $data): bool
    {
        return SwapRequest::where('id', $id)->update($data) > 0;
    }

    /**
     * Get swap request by ID
     */
    public function findById(int $id): ?SwapRequest
    {
        return SwapRequest::where('id', $id)
            ->with([
                'requester:id,name,nim',
                'target:id,name,nim',
                'requesterAssignment.schedule',
                'targetAssignment.schedule',
            ])
            ->first();
    }

    /**
     * Check for duplicate swap requests
     */
    public function hasDuplicateRequest(int $originalScheduleId): bool
    {
        return SwapRequest::where('original_schedule_assignment_id', $originalScheduleId)
            ->whereIn('status', ['pending', 'target_approved', 'admin_approved'])
            ->exists();
    }

    /**
     * Get swap statistics for user
     */
    public function getUserStats(int $userId): array
    {
        $requests = SwapRequest::where('user_id', $userId);
        $received = SwapRequest::where('target_id', $userId);

        return [
            'total_requests' => $requests->count(),
            'pending_requests' => $requests->where('status', 'pending')->count(),
            'approved_requests' => $requests->whereIn('status', ['target_approved', 'admin_approved'])->count(),
            'rejected_requests' => $requests->whereIn('status', ['target_rejected', 'admin_rejected'])->count(),
            'total_received' => $received->count(),
            'pending_received' => $received->where('status', 'pending')->count(),
            'approved_received' => $received->whereIn('status', ['target_approved', 'admin_approved'])->count(),
            'rejected_received' => $received->whereIn('status', ['target_rejected', 'admin_rejected'])->count(),
        ];
    }

    /**
     * Get pending swap requests for admin approval
     */
    public function getPendingForAdminApproval(): Collection
    {
        return SwapRequest::where('status', 'target_approved')
            ->with([
                'requester:id,name,nim',
                'target:id,name,nim',
                'requesterAssignment.schedule',
                'targetAssignment.schedule',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete swap request
     */
    public function delete(int $id): bool
    {
        return SwapRequest::where('id', $id)->delete() > 0;
    }

    /**
     * Get swap requests by date range
     */
    public function getByDateRange(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): Collection
    {
        return SwapRequest::whereHas('requesterAssignment', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        })
            ->orWhereHas('targetAssignment', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->with([
                'requester:id,name,nim',
                'target:id,name,nim',
                'requesterAssignment.schedule',
                'targetAssignment.schedule',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
