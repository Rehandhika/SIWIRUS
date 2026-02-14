<?php

namespace App\Policies;

use App\Models\Penalty;
use App\Models\User;

class PenaltyPolicy
{
    /**
     * Can view any penalties
     */
    public function viewAny(User $user): bool
    {
        return $user->can('kelola_pelanggaran') || $user->can('lihat_pelanggaran');
    }

    /**
     * Can view a specific penalty
     */
    public function view(User $user, Penalty $penalty): bool
    {
        return $user->id === $penalty->user_id ||
               $user->can('kelola_pelanggaran') ||
               $user->can('lihat_pelanggaran');
    }

    /**
     * Can create a penalty
     */
    public function create(User $user): bool
    {
        return $user->can('kelola_pelanggaran');
    }

    /**
     * Can update a penalty
     */
    public function update(User $user, Penalty $penalty): bool
    {
        return $user->can('kelola_pelanggaran');
    }

    /**
     * Can delete a penalty
     */
    public function delete(User $user, Penalty $penalty): bool
    {
        return $user->can('kelola_pelanggaran');
    }
}
