<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_antrean',
        'patient_id',
        'poli',
        'dokter_id',
        'tanggal_antrean',
        'waktu_daftar',
        'keluhan_singkat',
        'status',
        'prioritas',
        'is_emergency',
        'triage_by_user_id',
        'examined_by_user_id',
        'waktu_panggil',
        'waktu_selesai',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_antrean' => 'date',
            'waktu_panggil' => 'datetime',
            'waktu_selesai' => 'datetime',
            'is_emergency' => 'boolean',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'dokter_id');
    }

    public function triageNurse()
    {
        return $this->belongsTo(User::class, 'triage_by_user_id');
    }

    public function examiningDoctor()
    {
        return $this->belongsTo(User::class, 'examined_by_user_id');
    }

    public function vitalSign()
    {
        return $this->hasOne(VitalSign::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(QueueStatusHistory::class)->orderBy('recorded_at');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'menunggu_triage' => ['label' => 'Menunggu Triage', 'bg' => 'bg-amber-100 text-amber-800 border-amber-200', 'color' => '#d97706'],
            'siap_periksa' => ['label' => 'Siap Diperiksa', 'bg' => 'bg-blue-100 text-blue-800 border-blue-200', 'color' => '#2563eb'],
            'sedang_diperiksa' => ['label' => 'Sedang Diperiksa', 'bg' => 'bg-teal-100 text-teal-800 border-teal-200', 'color' => '#0f766e'],
            'menunggu_obat' => ['label' => 'Menunggu Farmasi', 'bg' => 'bg-purple-100 text-purple-800 border-purple-200', 'color' => '#7e22ce'],
            'sedang_dispensing' => ['label' => 'Sedang Disiapkan', 'bg' => 'bg-indigo-100 text-indigo-800 border-indigo-200', 'color' => '#4338ca'],
            'siap_diambil' => ['label' => 'Obat Siap Diambil', 'bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'color' => '#059669'],
            'selesai' => ['label' => 'Pelayanan Selesai', 'bg' => 'bg-slate-100 text-slate-700 border-slate-200', 'color' => '#475569'],
            default => ['label' => ucfirst($this->status), 'bg' => 'bg-gray-100 text-gray-800 border-gray-200', 'color' => '#6b7280'],
        };
    }
}
