<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    /** Only the original author for the encounter's clinical role may revise a final record. */
    public function revise(User $user, MedicalRecord $medicalRecord): bool
    {
        $medicalRecord->loadMissing('queue');
        $expectedRole = $medicalRecord->queue?->poli === 'gigi' ? 'dokter_gigi' : 'dokter';

        return $medicalRecord->status === 'final'
            && $medicalRecord->doctor_id === $user->id
            && $medicalRecord->queue !== null
            && $user->hasRole($expectedRole);
    }

    public function qualityReview(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->hasRole('perekam_medis') && $medicalRecord->status === 'final';
    }
}
