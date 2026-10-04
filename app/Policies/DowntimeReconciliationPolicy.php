<?php

namespace App\Policies;

use App\Models\DowntimeReconciliation;
use App\Models\User;

class DowntimeReconciliationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('perekam_medis');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DowntimeReconciliation $downtimeReconciliation): bool
    {
        return $user->hasRole('perekam_medis');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('dokter', 'dokter_gigi');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DowntimeReconciliation $downtimeReconciliation): bool
    {
        return $user->hasRole('perekam_medis');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DowntimeReconciliation $downtimeReconciliation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DowntimeReconciliation $downtimeReconciliation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DowntimeReconciliation $downtimeReconciliation): bool
    {
        return false;
    }
}
