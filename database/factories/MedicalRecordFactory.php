<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordRevision;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(static function (MedicalRecord $medicalRecord): void {
            MedicalRecordRevision::query()->create([
                'medical_record_id' => $medicalRecord->id,
                'version_number' => 1,
                'revision_type' => 'original',
                'reason' => 'Catatan sintetis untuk fixture pengembangan.',
                'snapshot_json' => $medicalRecord->only([
                    'subjective', 'objective', 'assessment', 'plan', 'icd10_primary_code',
                    'icd10_primary_name', 'status', 'disposition', 'doctor_id', 'signed_by_name', 'signed_at',
                ]),
                'recorded_by_user_id' => $medicalRecord->doctor_id,
                'recorded_by_name' => $medicalRecord->signed_by_name,
                'recorded_by_role' => $medicalRecord->signed_by_role,
                'origin' => 'application',
                'recorded_at' => $medicalRecord->signed_at,
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'queue_id' => Queue::factory()->state([
                'dokter_id' => User::factory()->state(['role' => 'dokter']),
            ]),
            'patient_id' => static fn (array $attributes): int => (int) Queue::query()->findOrFail($attributes['queue_id'])->patient_id,
            'doctor_id' => static fn (array $attributes): int => (int) Queue::query()->findOrFail($attributes['queue_id'])->dokter_id,
            'signed_by_user_id' => static fn (array $attributes): int => (int) Queue::query()->findOrFail($attributes['queue_id'])->dokter_id,
            'signed_by_name' => static fn (array $attributes): string => (string) User::query()->findOrFail(Queue::query()->findOrFail($attributes['queue_id'])->dokter_id)->name,
            'signed_by_role' => 'dokter',
            'signed_at' => now(),
            'subjective' => fake()->paragraph(),
            'objective' => fake()->paragraph(),
            'assessment' => fake()->paragraph(),
            'plan' => fake()->paragraph(),
            'icd10_primary_code' => 'R69',
            'icd10_primary_name' => 'Penyakit yang tidak diketahui atau tidak ditentukan',
            'status' => 'final',
            'disposition' => 'pulang',
        ];
    }
}
