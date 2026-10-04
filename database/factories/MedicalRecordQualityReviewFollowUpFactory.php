<?php

namespace Database\Factories;

use App\Models\MedicalRecordQualityReview;
use App\Models\MedicalRecordQualityReviewFollowUp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<MedicalRecordQualityReviewFollowUp>
 */
class MedicalRecordQualityReviewFollowUpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_quality_review_id' => MedicalRecordQualityReview::factory()->state([
                'review_result' => 'needs_follow_up',
                'checklist_json' => [
                    'patient_identity' => 'pass',
                    'encounter_context' => 'pass',
                    'soap_sections' => 'finding',
                    'coded_diagnosis' => 'pass',
                    'signer_metadata' => 'pass',
                    'disposition' => 'pass',
                ],
                'findings' => 'Catatan sintetis memerlukan kelengkapan.',
            ]),
            'item_key' => 'soap_sections',
            'item_label' => 'Catatan subjektif, objektif, asesmen, dan rencana terdokumentasi',
            'finding' => 'Catatan sintetis memerlukan kelengkapan.',
            'assigned_to_user_id' => static fn (array $attributes): int => (int) DB::table('medical_record_quality_reviews')
                ->join('medical_records', 'medical_records.id', '=', 'medical_record_quality_reviews.medical_record_id')
                ->where('medical_record_quality_reviews.id', $attributes['medical_record_quality_review_id'])
                ->value('medical_records.doctor_id'),
            'due_at' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ];
    }
}
