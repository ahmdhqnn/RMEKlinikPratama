<?php

namespace App\Models;

use Database\Factories\QualityReviewSamplingItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityReviewSamplingItem extends Model
{
    /** @use HasFactory<QualityReviewSamplingItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sampling_run_id',
        'medical_record_id',
        'poli',
        'selection_reason',
        'selected_at',
    ];

    protected function casts(): array
    {
        return ['selected_at' => 'datetime'];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(QualityReviewSamplingRun::class, 'sampling_run_id');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
