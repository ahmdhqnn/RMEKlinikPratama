<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\QualityReviewSamplingItem;
use App\Models\QualityReviewSamplingRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityReviewSamplingItem>
 */
class QualityReviewSamplingItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sampling_run_id' => QualityReviewSamplingRun::factory(),
            'medical_record_id' => MedicalRecord::factory(),
            'poli' => 'umum',
            'selection_reason' => 'routine',
            'selected_at' => now(),
        ];
    }
}
