<?php

namespace App\Repositories\Contracts;

use App\Models\Medicine;
use App\Models\MedicineStockMovement;

interface MedicineInventoryRepositoryInterface
{
    public function lockMedicine(Medicine $medicine): Medicine;

    /** @param array<string, mixed> $attributes */
    public function createMovement(array $attributes): MedicineStockMovement;
}
