<?php

namespace App\Models;

use Database\Factories\QualityReviewSamplingRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityReviewSamplingRun extends Model
{
    /** @use HasFactory<QualityReviewSamplingRunFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'period_start',
        'period_end',
        'requested_sample_size',
        'selected_sample_size',
        'population_counts_json',
        'selection_seed',
        'sampling_method',
        'created_by_user_id',
        'created_by_name',
        'created_by_role',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'created_at' => 'datetime',
            'population_counts_json' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QualityReviewSamplingItem::class, 'sampling_run_id')->orderBy('poli')->orderBy('selection_reason');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
