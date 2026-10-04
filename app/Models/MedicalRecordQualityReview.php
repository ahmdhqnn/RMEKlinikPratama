<?php

namespace App\Models;

use Database\Factories\MedicalRecordQualityReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecordQualityReview extends Model
{
    /** @use HasFactory<MedicalRecordQualityReviewFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'medical_record_id',
        'review_round',
        'reviewed_record_version',
        'review_result',
        'checklist_json',
        'findings',
        'reviewed_by_user_id',
        'reviewed_by_name',
        'reviewed_by_role',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'checklist_json' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new \LogicException('Medical record quality reviews are append-only.');
        });

        static::deleting(static function (): never {
            throw new \LogicException('Medical record quality reviews are append-only.');
        });
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(MedicalRecordQualityReviewFollowUp::class, 'medical_record_quality_review_id');
    }
}
