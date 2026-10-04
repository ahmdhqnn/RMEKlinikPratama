<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'patient_id',
        'doctor_id',
        'signed_by_user_id',
        'signed_by_name',
        'signed_by_role',
        'signed_at',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'icd10_primary_code',
        'icd10_primary_name',
        'icd10_secondary_json',
        'tindakan_medis',
        'status',
        'disposition',
        'follow_up_at',
        'follow_up_instructions',
        'satusehat_encounter_id',
        'satusehat_sync_status',
    ];

    protected function casts(): array
    {
        return [
            'icd10_secondary_json' => 'array',
            'follow_up_at' => 'date',
            'signed_at' => 'datetime',
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

    public function odontogram()
    {
        return $this->hasOne(Odontogram::class);
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class, 'queue_id', 'queue_id');
    }

    public function attachments()
    {
        return $this->hasMany(MedicalAttachment::class);
    }

    public function letters()
    {
        return $this->hasMany(MedicalLetter::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(MedicalRecordRevision::class)->orderBy('version_number');
    }

    public function qualityReviews(): HasMany
    {
        return $this->hasMany(MedicalRecordQualityReview::class)->orderBy('review_round');
    }

    public function downtimeReconciliations(): HasMany
    {
        return $this->hasMany(DowntimeReconciliation::class);
    }
}
