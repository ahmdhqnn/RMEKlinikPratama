<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Queue>
 */
class QueueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_antrean' => fake()->numerify('A-###'),
            'patient_id' => Patient::factory(),
            'poli' => 'umum',
            'tanggal_antrean' => now()->toDateString(),
            'status' => 'selesai',
            'prioritas' => 'normal',
            'is_emergency' => false,
        ];
    }
}
