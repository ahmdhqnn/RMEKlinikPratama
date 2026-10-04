<?php

namespace App\Policies;

use App\Models\MedicalLetter;
use App\Models\User;

class MedicalLetterPolicy
{
    public function print(User $user, MedicalLetter $medicalLetter): bool
    {
        if (! $user->hasRole('dokter', 'dokter_gigi') || $medicalLetter->doctor_id !== $user->id) {
            return false;
        }

        $medicalLetter->loadMissing('medicalRecord.queue');
        $queue = $medicalLetter->medicalRecord?->queue;
        if ($queue === null || $medicalLetter->medicalRecord?->patient_id !== $medicalLetter->patient_id) {
            return false;
        }

        $expectedRole = $queue->poli === 'gigi' ? 'dokter_gigi' : 'dokter';

        return $user->hasRole($expectedRole);
    }
}
