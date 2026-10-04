<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'no_rm',
        'nik',
        'nik_hash',
        'nip',
        'nama_lengkap',
        'identity_status',
        'identity_verified_at',
        'identity_verification_method',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'golongan_darah',
        'no_bpjs',
        'jenis_pasien',
        'unit_kerja',
        'no_hp',
        'alamat',
        'riwayat_alergi',
        'alergi_keterangan',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'tanggal_lahir' => 'date',
            'identity_verified_at' => 'datetime',
        ];
    }

    public function getUsiaAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }

    public function queues()
    {
        return $this->hasMany(Queue::class)->orderBy('created_at', 'desc');
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class)->orderBy('created_at', 'desc');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class)->orderBy('created_at', 'desc');
    }

    public function odontograms()
    {
        return $this->hasMany(Odontogram::class)->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->hasMany(MedicalAttachment::class)->orderBy('created_at', 'desc');
    }

    public function letters()
    {
        return $this->hasMany(MedicalLetter::class)->orderBy('created_at', 'desc');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class)->orderBy('created_at', 'desc');
    }
}
