<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineStockMovement extends Model
{
    protected $fillable = [
        'medicine_id',
        'user_id',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'reason',
        'reference',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
