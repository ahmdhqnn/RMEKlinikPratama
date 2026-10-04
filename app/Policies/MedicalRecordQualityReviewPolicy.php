<?php

namespace App\Policies;

use App\Models\MedicalRecordQualityReview;
use App\Models\User;

class MedicalRecordQualityReviewPolicy
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
    public function view(User $user, MedicalRecordQualityReview $medicalRecordQualityReview): bool
    {
        return $user->hasRole('perekam_medis');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('perekam_medis');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MedicalRecordQualityReview $medicalRecordQualityReview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MedicalRecordQualityReview $medicalRecordQualityReview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MedicalRecordQualityReview $medicalRecordQualityReview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MedicalRecordQualityReview $medicalRecordQualityReview): bool
    {
        return false;
    }
}
