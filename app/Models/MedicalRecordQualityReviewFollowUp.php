<?php

namespace App\Models;

use App\Enums\MedicalRecordQualityReviewFollowUpStatus;
use Database\Factories\MedicalRecordQualityReviewFollowUpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordQualityReviewFollowUp extends Model
{
    /** @use HasFactory<MedicalRecordQualityReviewFollowUpFactory> */
    use HasFactory;

    protected $fillable = [
        'medical_record_quality_review_id',
        'item_key',
        'item_label',
        'finding',
        'assigned_to_user_id',
        'due_at',
        'status',
        'resolution_note',
        'resolved_by_user_id',
        'resolved_record_version',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'status' => MedicalRecordQualityReviewFollowUpStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function qualityReview(): BelongsTo
    {
        return $this->belongsTo(MedicalRecordQualityReview::class, 'medical_record_quality_review_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
