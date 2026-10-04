<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'jumlah',
        'aturan_pakai',
        'catatan_khusus',
        'is_dispensed',
    ];

    protected function casts(): array
    {
        return [
            'is_dispensed' => 'boolean',
            'jumlah' => 'integer',
        ];
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
