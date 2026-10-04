<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitalSign extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'patient_id',
        'nurse_id',
        'sistolik',
        'diastolik',
        'suhu',
        'nadi',
        'laju_nafas',
        'spo2',
        'berat_badan',
        'tinggi_badan',
        'imt',
        'kesadaran',
        'skala_nyeri',
        'catatan_keperawatan',
    ];

    protected function casts(): array
    {
        return [
            'suhu' => 'float',
            'berat_badan' => 'float',
            'tinggi_badan' => 'float',
            'imt' => 'float',
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

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function getBpStatusAttribute(): string
    {
        if (! $this->sistolik || ! $this->diastolik) {
            return 'Belum Diukur';
        }
        if ($this->sistolik < 120 && $this->diastolik < 80) {
            return 'Optimal / Normal';
        }
        if ($this->sistolik <= 129 && $this->diastolik < 80) {
            return 'Elevated';
        }
        if (($this->sistolik >= 130 && $this->sistolik <= 139) || ($this->diastolik >= 80 && $this->diastolik <= 89)) {
            return 'Pre-Hipertensi / HT Grade 1';
        }

        return 'Hipertensi Stage 2';
    }

    public function getTempStatusAttribute(): string
    {
        if (! $this->suhu) {
            return 'Belum Diukur';
        }
        if ($this->suhu < 36.0) {
            return 'Hipotermia';
        }
        if ($this->suhu <= 37.5) {
            return 'Afebris (Normal)';
        }
        if ($this->suhu <= 38.3) {
            return 'Sub-febris';
        }

        return 'Febris (Demam)';
    }
}
