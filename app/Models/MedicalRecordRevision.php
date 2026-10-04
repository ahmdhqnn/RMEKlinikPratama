<?php

namespace App\Models;

use Database\Factories\MedicalRecordRevisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecordRevision extends Model
{
    /** @use HasFactory<MedicalRecordRevisionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'medical_record_id',
        'version_number',
        'revision_type',
        'reason',
        'snapshot_json',
        'addendum_text',
        'recorded_by_user_id',
        'recorded_by_name',
        'recorded_by_role',
        'origin',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new \LogicException('Medical record revisions are append-only.');
        });

        static::deleting(static function (): never {
            throw new \LogicException('Medical record revisions are append-only.');
        });
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
