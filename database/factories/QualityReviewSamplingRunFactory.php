<?php

namespace Database\Factories;

use App\Models\QualityReviewSamplingRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityReviewSamplingRun>
 */
class QualityReviewSamplingRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = now()->startOfMonth()->toDateString();

        return [
            'period_start' => $periodStart,
            'period_end' => now()->endOfMonth()->toDateString(),
            'requested_sample_size' => 5,
            'selected_sample_size' => 1,
            'population_counts_json' => ['umum' => 1],
            'selection_seed' => fake()->sha256(),
            'sampling_method' => 'stratified_risk_and_routine',
            'created_by_user_id' => User::factory()->state(['role' => 'perekam_medis']),
            'created_by_name' => static fn (array $attributes): string => (string) User::query()->findOrFail($attributes['created_by_user_id'])->name,
            'created_by_role' => 'perekam_medis',
            'created_at' => now(),
        ];
    }
}
