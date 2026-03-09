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
     * Users with 'kelola_jadwal' permission can edit published schedules
     */
    public function edit(User $user, Schedule $schedule): bool
    {
        // Check if user has permission to manage schedules
        if (! $user->can('kelola_jadwal')) {
            return false;
        }

        // Schedule must be published or draft (not archived)
        // Archived schedules can only be edited by Super Admin via forceEdit
        return in_array($schedule->status, ['published', 'draft']);
    }

    /**
     * Determine if the user can force edit any schedule (including archived)
     * Requires 'kelola_jadwal' permission
     */
    public function forceEdit(User $user, Schedule $schedule): bool
    {
        return $user->can('kelola_jadwal');
    }

    /**
     * Determine if the user can view edit history
     * Users with schedule management or viewing permissions can view history
     */
    public function viewHistory(User $user, Schedule $schedule): bool
    {
        return $user->can('kelola_jadwal') || $user->can('lihat_semua_jadwal') || $user->can('lihat_jadwal_sendiri');
    }
}
