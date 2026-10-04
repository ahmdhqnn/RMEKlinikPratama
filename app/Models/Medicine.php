<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_obat',
        'nama_obat',
        'kategori',
        'bentuk_sediaan',
        'satuan',
        'stok',
        'stok_minimum',
        'nomor_batch',
        'expired_date',
        'harga_satuan',
        'aturan_pakai_default',
        'kontraindikasi_alergi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expired_date' => 'date',
            'is_active' => 'boolean',
            'harga_satuan' => 'float',
            'stok' => 'integer',
        ];
    }

    public function isLowStock(): bool
    {
        return $this->stok <= $this->stok_minimum;
    }

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(MedicineStockMovement::class);
    }
}
