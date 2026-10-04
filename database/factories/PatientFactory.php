<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_rm' => fake()->unique()->numerify('RM########'),
            'nama_lengkap' => fake()->name(),
            'identity_status' => 'provisional',
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'jenis_pasien' => 'Umum',
        ];
    }
}
