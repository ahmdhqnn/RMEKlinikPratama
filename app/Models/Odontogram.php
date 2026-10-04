<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Odontogram extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'patient_id',
        'dentist_id',
        'teeth_data_json',
        'oklusi',
        'torus_palatinus',
        'torus_mandibularis',
        'palatum',
        'diastema',
        'anomali_gigi',
        'catatan_khusus',
    ];

    protected function casts(): array
    {
        return [
            'teeth_data_json' => 'array',
        ];
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
}
