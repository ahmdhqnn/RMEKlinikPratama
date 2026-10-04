<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_obat' => strtoupper(Str::random(10)),
            'nama_obat' => fake()->unique()->words(2, true),
            'kategori' => 'Generik',
            'bentuk_sediaan' => 'Tablet',
            'satuan' => 'tablet',
            'stok' => 100,
            'stok_minimum' => 10,
            'expired_date' => today()->addYear(),
            'harga_satuan' => 1000,
            'is_active' => true,
        ];
    }
}
