<?php

namespace App\Policies;

use App\Models\Medicine;
use App\Models\User;

class MedicinePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function receiveStock(User $user, Medicine $medicine): bool
    {
        return $user->hasRole('farmasi') && $medicine->is_active;
    }
}
