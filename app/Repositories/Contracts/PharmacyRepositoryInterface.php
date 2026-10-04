<?php

namespace App\Repositories\Contracts;

use App\Models\Medicine;
use App\Models\Prescription;
use Illuminate\Support\Collection;

interface PharmacyRepositoryInterface
{
    /** @return array<string, mixed> */
    public function board(string $search): array;

    public function findPrescription(int $id): Prescription;

    public function lockPrescription(Prescription $prescription): Prescription;

    public function lockMedicine(int $id): Medicine;

    /** @param array<string, mixed> $attributes */
    public function createPrescription(array $attributes): Prescription;

    /** @param list<array<string, mixed>> $items */
    public function createItems(Prescription $prescription, array $items): Collection;
}
