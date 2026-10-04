<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecordQualityReview>
 */
class MedicalRecordQualityReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'review_round' => 1,
            'reviewed_record_version' => 1,
            'review_result' => 'complete',
            'checklist_json' => [
                'patient_identity' => 'pass',
                'encounter_context' => 'pass',
                'soap_sections' => 'pass',
                'coded_diagnosis' => 'pass',
                'signer_metadata' => 'pass',
                'disposition' => 'pass',
            ],
            'findings' => null,
            'reviewed_by_user_id' => User::factory()->state(['role' => 'perekam_medis']),
            'reviewed_by_name' => static fn (array $attributes): string => (string) User::query()->findOrFail($attributes['reviewed_by_user_id'])->name,
            'reviewed_by_role' => 'perekam_medis',
            'reviewed_at' => now(),
        ];
    }
}
