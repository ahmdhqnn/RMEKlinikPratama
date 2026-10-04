<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'medical_record_id',
        'doctor_id',
        'jenis_surat',
        'nomor_surat',
        'tanggal_surat',
        'lama_hari',
        'tanggal_mulai',
        'tanggal_selesai',
        'tujuan_faskes_rujukan',
        'poli_tujuan_rujukan',
        'alasan_rujukan',
        'icd10_code',
        'pemeriksaan_lab_diminta',
        'keperluan',
        'status_kesehatan',
        'catatan_tambahan',
        'qr_token',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis_surat) {
            'surat_sakit' => 'Draf Catatan Istirahat Sakit Klinik',
            'surat_sehat' => 'Surat Keterangan Sehat Fisik',
            'surat_rujukan' => 'Surat Rujukan Pasien',
            'pengantar_lab' => 'Surat Pengantar Pemeriksaan Penunjang Luar',
            default => 'Surat Keterangan Medis',
        };
    }
}
