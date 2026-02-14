<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Can view any leave requests
     */
    public function viewAny(User $user): bool
    {
        return $user->can('kelola_cuti') || $user->can('ajukan_cuti');
    }

    /**
     * Can view a specific leave request
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->id === $leaveRequest->user_id ||
               $user->can('kelola_cuti');
    }

    /**
     * Can create a leave request
     */
    public function create(User $user): bool
    {
        return $user->can('ajukan_cuti') && $user->isActive();
    }

    /**
     * Can update a leave request
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->id === $leaveRequest->user_id &&
               $leaveRequest->status === 'pending';
    }

    /**
     * Can delete a leave request
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->id === $leaveRequest->user_id &&
               $leaveRequest->status === 'pending';
    }

    /**
     * Can approve/reject a leave request
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('kelola_cuti') &&
               $leaveRequest->status === 'pending';
    }
}
