<?php

namespace App\Policies;

use App\Models\MedicalAttachment;
use App\Models\Patient;
use App\Models\User;

class MedicalAttachmentPolicy
{
    public function view(User $user, Patient $patient, MedicalAttachment $attachment): bool
    {
        if ($attachment->patient_id !== $patient->id || ! app(PatientPolicy::class)->view($user, $patient)) {
            return false;
        }

        if ($attachment->uploaded_by_user_id === $user->id) {
            return true;
        }

        $attachment->loadMissing('medicalRecord.queue');
        $record = $attachment->medicalRecord;
        $expectedRole = $record?->queue?->poli === 'gigi' ? 'dokter_gigi' : 'dokter';

        return $record?->patient_id === $patient->id
            && $record->doctor_id === $user->id
            && $user->hasRole($expectedRole);
    }
}
