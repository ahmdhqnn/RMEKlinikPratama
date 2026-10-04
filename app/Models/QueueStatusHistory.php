<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'queue_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'changed_by_name',
        'changed_by_role',
        'reason',
        'origin',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new \LogicException('Queue status history is append-only.');
        });

        static::deleting(static function (): never {
            throw new \LogicException('Queue status history is append-only.');
        });
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(Queue::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
