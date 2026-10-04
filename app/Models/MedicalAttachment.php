<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'medical_record_id',
        'uploaded_by_user_id',
        'kategori',
        'judul_dokumen',
        'instansi_penerbit',
        'tanggal_dokumen',
        'file_path',
        'file_type',
        'file_size_kb',
        'catatan_interpretasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_dokumen' => 'date',
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

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getKategoriBadgeAttribute(): array
    {
        return match ($this->kategori) {
            'lab_luar' => ['label' => 'Hasil Lab Luar', 'icon' => 'science', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'rontgen_radiologi' => ['label' => 'Foto Rontgen / Radiologi', 'icon' => 'radiology', 'color' => 'bg-blue-50 text-blue-700 border-blue-200'],
            'jawaban_rujukan' => ['label' => 'Surat Rujukan Balik', 'icon' => 'forward_to_inbox', 'color' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'resume_medis_luar' => ['label' => 'Resume Medis Luar', 'icon' => 'clinical_notes', 'color' => 'bg-amber-50 text-amber-700 border-amber-200'],
            default => ['label' => ucfirst($this->kategori), 'icon' => 'attach_file', 'color' => 'bg-gray-50 text-gray-700 border-gray-200'],
        };
    }
}
