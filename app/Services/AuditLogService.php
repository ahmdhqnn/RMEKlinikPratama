<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    /** @param array<string, mixed>|null $payload */
    public function record(
        string $action,
        string $description,
        ?User $user = null,
        ?int $patientId = null,
        ?array $payload = null,
    ): AuditLog {
        $user ??= auth()->user();

        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_role' => $user?->role ?? 'system',
            'action' => $action,
            'patient_id' => $patientId,
            'description' => $description,
            'payload_json' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
