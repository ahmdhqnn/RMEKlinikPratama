<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('pendaftaran', 'perawat', 'dokter', 'dokter_gigi');
    }

    public function view(User $user, Patient $patient): bool
    {
        if (! $user->hasRole('dokter', 'dokter_gigi')) {
            return false;
        }

        $poli = $user->role === 'dokter_gigi' ? 'gigi' : 'umum';

        return $patient->queues()
            ->where('dokter_id', $user->id)
            ->where('poli', $poli)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('pendaftaran');
    }

    public function printCard(User $user, Patient $patient): bool
    {
        return $user->hasRole('pendaftaran');
    }

    public function reconcileIdentity(User $user, Patient $patient): bool
    {
        return $user->hasRole('pendaftaran');
    }

    public function uploadAttachment(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }
}
