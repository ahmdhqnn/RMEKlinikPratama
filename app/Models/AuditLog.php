<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // created_at handled by database default

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'patient_id',
        'description',
        'payload_json',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Enforce append-only / immutable log: disallow update and delete operations
     */
    protected static function booted()
    {
        static::updating(function () {
            throw new \Exception('AuditLog is strictly immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \Exception('AuditLog is strictly immutable and cannot be deleted.');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public static function logAction(string $action, string $description, ?int $patientId = null, ?array $payload = null): self
    {
        $user = auth()->user();

        return self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_role' => $user?->role ?? 'system',
            'action' => $action,
            'patient_id' => $patientId,
            'description' => $description,
            'payload_json' => $payload,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'Clinic-Browser',
            'created_at' => now(),
        ]);
    }
}
