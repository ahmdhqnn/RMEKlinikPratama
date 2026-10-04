<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_resep',
        'queue_id',
        'patient_id',
        'doctor_id',
        'pharmacist_id',
        'status',
        'catatan_dokter_farmasi',
        'catatan_apoteker',
        'waktu_verifikasi',
        'waktu_penyerahan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_verifikasi' => 'datetime',
            'waktu_penyerahan' => 'datetime',
        ];
    }

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['label' => 'Resep Masuk', 'bg' => 'bg-amber-100 text-amber-800 border-amber-200', 'color' => '#d97706'],
            'processing' => ['label' => 'Sedang Disiapkan', 'bg' => 'bg-blue-100 text-blue-800 border-blue-200', 'color' => '#2563eb'],
            'ready' => ['label' => 'Siap Diambil', 'bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'color' => '#059669'],
            'dispensed' => ['label' => 'Selesai Diserahkan', 'bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'color' => '#475569'],
            default => ['label' => ucfirst($this->status), 'bg' => 'bg-gray-100 text-gray-800 border-gray-200', 'color' => '#6b7280'],
        };
    }
}
