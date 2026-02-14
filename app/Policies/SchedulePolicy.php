<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('kelola_jadwal') || $user->can('lihat_jadwal');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $user->can('kelola_jadwal') || $user->can('lihat_jadwal');
    }

    public function create(User $user): bool
    {
        return $user->can('kelola_jadwal');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->can('kelola_jadwal') && $schedule->canEdit();
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->can('kelola_jadwal') && $schedule->isDraft();
    }

    public function generate(User $user): bool
    {
        return $user->can('kelola_jadwal');
    }

    public function publish(User $user, Schedule $schedule): bool
    {
        return $user->can('kelola_jadwal') && $schedule->isDraft();
    }

    /**
     * Determine if the user can edit a published schedule
     * Admins and Super Admins can edit published schedules
     */
    public function edit(User $user, Schedule $schedule): bool
    {
        // Check if user has Admin or Super Admin role
        if (! $user->hasRole(['Super Admin', 'Admin'])) {
            return false;
        }

        // Schedule must be published or draft (not archived)
        // Archived schedules can only be edited by Super Admin via forceEdit
        return in_array($schedule->status, ['published', 'draft']);
    }

    /**
     * Determine if the user can force edit any schedule (including archived)
     * Only Super Admins can force edit
     */
    public function forceEdit(User $user, Schedule $schedule): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine if the user can view edit history
     * Admins, Super Admins, and Pengurus can view history
     */
    public function viewHistory(User $user, Schedule $schedule): bool
    {
        return $user->hasRole(['Super Admin', 'Admin', 'Pengurus']);
    }
}
