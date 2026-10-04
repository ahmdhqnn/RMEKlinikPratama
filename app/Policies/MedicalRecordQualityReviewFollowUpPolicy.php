<?php

namespace App\Policies;

use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Models\User;

class MedicalRecordQualityReviewFollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('perekam_medis', 'dokter', 'dokter_gigi');
    }

    public function view(User $user, MedicalRecordQualityReviewFollowUp $followUp): bool
    {
        return $user->hasRole('perekam_medis') || $this->isAssignedClinician($user, $followUp);
    }

    public function update(User $user, MedicalRecordQualityReviewFollowUp $followUp): bool
    {
        return $this->isAssignedClinician($user, $followUp);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, MedicalRecordQualityReviewFollowUp $followUp): bool
    {
        return false;
    }

    private function isAssignedClinician(User $user, MedicalRecordQualityReviewFollowUp $followUp): bool
    {
        $followUp->loadMissing('qualityReview.medicalRecord.queue');
        $expectedRole = $followUp->qualityReview->medicalRecord->queue?->poli === 'gigi' ? 'dokter_gigi' : 'dokter';

        return $followUp->assigned_to_user_id === $user->id && $user->hasRole($expectedRole);
    }
}
