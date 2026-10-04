<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('farmasi');
    }

    public function verify(User $user, Prescription $prescription): bool
    {
        return $user->hasRole('farmasi') && $prescription->status === 'pending';
    }

    public function markReady(User $user, Prescription $prescription): bool
    {
        return $user->hasRole('farmasi') && $prescription->status === 'processing';
    }

    public function dispense(User $user, Prescription $prescription): bool
    {
        return $user->hasRole('farmasi') && $prescription->status === 'ready';
    }

    public function printLabel(User $user, Prescription $prescription): bool
    {
        return $user->hasRole('farmasi') && in_array($prescription->status, ['processing', 'ready', 'dispensed'], true);
    }
}
