<?php

namespace App\Repositories\Eloquent;

use App\Models\Medicine;
use App\Models\MedicineStockMovement;
use App\Repositories\Contracts\MedicineInventoryRepositoryInterface;

class EloquentMedicineInventoryRepository implements MedicineInventoryRepositoryInterface
{
    public function lockMedicine(Medicine $medicine): Medicine
    {
        return Medicine::query()->whereKey($medicine->getKey())->lockForUpdate()->firstOrFail();
    }

    /** @param array<string, mixed> $attributes */
    public function createMovement(array $attributes): MedicineStockMovement
    {
        return MedicineStockMovement::query()->create($attributes);
    }
}
