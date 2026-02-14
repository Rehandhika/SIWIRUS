<?php

namespace App\Policies;

use App\Models\ScheduleChangeRequest;
use App\Models\User;

class ScheduleChangeRequestPolicy
{
    /**
     * Can view any schedule change requests
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ajukan_tukar_jadwal') || $user->can('kelola_tukar_jadwal');
    }

    /**
     * Can view a specific schedule change request
     */
    public function view(User $user, ScheduleChangeRequest $request): bool
    {
        return $user->id === $request->user_id ||
               $user->can('kelola_tukar_jadwal');
    }

    /**
     * Can create a schedule change request
     */
    public function create(User $user): bool
    {
        return $user->can('ajukan_tukar_jadwal') && $user->isActive();
    }

    /**
     * Can update a schedule change request
     */
    public function update(User $user, ScheduleChangeRequest $request): bool
    {
        return $user->id === $request->user_id &&
               $request->status === 'pending';
    }

    /**
     * Can delete a schedule change request
     */
    public function delete(User $user, ScheduleChangeRequest $request): bool
    {
        return $user->id === $request->user_id &&
               $request->status === 'pending';
    }

    /**
     * Can approve/reject a schedule change request
     */
    public function approve(User $user, ScheduleChangeRequest $request): bool
    {
        return $user->can('kelola_tukar_jadwal') &&
               $request->status === 'pending';
    }
}
