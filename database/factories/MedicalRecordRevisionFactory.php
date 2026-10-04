<?php

namespace Database\Factories;

use App\Models\MedicalRecordRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecordRevision>
 */
class MedicalRecordRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_id' => 1,
            'version_number' => 2,
            'revision_type' => 'addendum',
            'reason' => fake()->sentence(),
            'snapshot_json' => [
                'subjective' => fake()->sentence(),
                'objective' => fake()->sentence(),
                'assessment' => fake()->sentence(),
                'plan' => fake()->sentence(),
                'status' => 'final',
            ],
            'addendum_text' => fake()->paragraph(),
            'recorded_by_user_id' => null,
            'recorded_by_name' => fake()->name(),
            'recorded_by_role' => 'dokter',
            'origin' => 'application',
            'recorded_at' => now(),
        ];
    }
}
