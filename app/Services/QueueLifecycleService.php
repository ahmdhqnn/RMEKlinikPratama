<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\User;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueLifecycleService
{
    public function __construct(
        private QueueRepositoryInterface $queues,
        private AuditLogService $auditLogService,
    ) {}

    public function recordInitialStatus(Queue $queue, User $actor): void
    {
        abort_unless($actor->hasRole('pendaftaran'), 403);
        abort_unless($queue->exists, 404);

        if ($queue->status !== QueueStatus::WaitingForTriage->value) {
            throw ValidationException::withMessages(['queue' => 'Antrean baru harus dimulai pada status menunggu triase.']);
        }

        $this->queues->createStatusHistory([
            'queue_id' => $queue->id,
            'from_status' => null,
            'to_status' => QueueStatus::WaitingForTriage->value,
            'changed_by_user_id' => $actor->id,
            'changed_by_name' => $actor->name,
            'changed_by_role' => $actor->role,
            'reason' => 'Antrean didaftarkan melalui front office.',
            'origin' => 'registration',
            'recorded_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $attributes */
    public function transition(
        Queue $queue,
        QueueStatus $nextStatus,
        User $actor,
        string $reason,
        array $attributes = [],
    ): Queue {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['queue' => 'Alasan transisi status wajib dicatat.']);
        }

        return DB::transaction(function () use ($queue, $nextStatus, $actor, $reason, $attributes): Queue {
            $lockedQueue = $this->queues->lock($queue);
            $currentStatus = QueueStatus::tryFrom($lockedQueue->status);

            if ($currentStatus === null || ! $currentStatus->canTransitionTo($nextStatus)) {
                throw ValidationException::withMessages([
                    'queue' => 'Perubahan status antrean tidak diizinkan dari status saat ini.',
                ]);
            }

            abort_unless($this->actorMayTransition($lockedQueue, $currentStatus, $nextStatus, $actor), 403);

            $lockedQueue->update(array_merge($attributes, ['status' => $nextStatus->value]));

            $this->queues->createStatusHistory([
                'queue_id' => $lockedQueue->id,
                'from_status' => $currentStatus->value,
                'to_status' => $nextStatus->value,
                'changed_by_user_id' => $actor->id,
                'changed_by_name' => $actor->name,
                'changed_by_role' => $actor->role,
                'reason' => trim($reason),
                'origin' => 'workflow',
                'recorded_at' => now(),
            ]);

            $this->auditLogService->record(
                'QUEUE_STATUS_CHANGED',
                'Status antrean diperbarui melalui workflow layanan.',
                $actor,
                $lockedQueue->patient_id,
                [
                    'queue_id' => $lockedQueue->id,
                    'from_status' => $currentStatus->value,
                    'to_status' => $nextStatus->value,
                    'reason' => trim($reason),
                ],
            );

            return $lockedQueue->refresh();
        }, attempts: 3);
    }

    private function actorMayTransition(Queue $queue, QueueStatus $current, QueueStatus $next, User $actor): bool
    {
        $expectedDoctorRole = $queue->poli === 'gigi' ? 'dokter_gigi' : 'dokter';
        $assignedDoctor = $actor->hasRole($expectedDoctorRole)
            && ($queue->dokter_id === null || $queue->dokter_id === $actor->id);

        return match ($current) {
            QueueStatus::WaitingForTriage => $next === QueueStatus::ReadyForExamination && $actor->hasRole('perawat'),
            QueueStatus::ReadyForExamination => $next === QueueStatus::UnderExamination && $assignedDoctor,
            QueueStatus::UnderExamination => in_array($next, [QueueStatus::WaitingForPharmacy, QueueStatus::Completed], true)
                && $assignedDoctor
                && ($queue->examined_by_user_id === null || $queue->examined_by_user_id === $actor->id),
            QueueStatus::WaitingForPharmacy => $next === QueueStatus::BeingDispensed && $actor->hasRole('farmasi'),
            QueueStatus::BeingDispensed => $next === QueueStatus::ReadyForPickup && $actor->hasRole('farmasi'),
            QueueStatus::ReadyForPickup => $next === QueueStatus::Completed && $actor->hasRole('farmasi'),
            QueueStatus::Completed, QueueStatus::Cancelled => false,
        };
    }
}
