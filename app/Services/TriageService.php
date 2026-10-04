<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\User;
use App\Repositories\Contracts\ClinicalRecordRepositoryInterface;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TriageService
{
    public function __construct(
        private QueueRepositoryInterface $queues,
        private ClinicalRecordRepositoryInterface $records,
        private AuditLogService $auditLogService,
        private QueueLifecycleService $lifecycle,
    ) {}

    public function waitingQueues(): Collection
    {
        return $this->queues->todayForTriage();
    }

    /** @param array<string, mixed> $data */
    public function recordVitals(Queue $queue, User $nurse, array $data): void
    {
        abort_unless($nurse->hasRole('perawat'), 403);

        DB::transaction(function () use ($queue, $nurse, $data): void {
            $lockedQueue = $this->queues->lock($queue);

            if ($lockedQueue->tanggal_antrean?->isToday() !== true || $lockedQueue->status !== QueueStatus::WaitingForTriage->value) {
                throw ValidationException::withMessages([
                    'queue' => 'Tanda vital hanya dapat dicatat untuk antrean yang menunggu triase.',
                ]);
            }

            $bmi = null;

            if (filled($data['berat_badan'] ?? null) && filled($data['tinggi_badan'] ?? null)) {
                $heightInMeters = $data['tinggi_badan'] / 100;
                $bmi = round($data['berat_badan'] / ($heightInMeters ** 2), 1);
            }

            $this->records->saveVitalSign($lockedQueue, $nurse, array_merge($data, ['imt' => $bmi]));
            $this->lifecycle->transition(
                $lockedQueue,
                QueueStatus::ReadyForExamination,
                $nurse,
                'Triase dicatat dan antrean dinyatakan siap diperiksa.',
                ['triage_by_user_id' => $nurse->id],
            );

            $this->auditLogService->record(
                'INPUT_TTV',
                'Tanda vital dan catatan triase dicatat; antrean siap diperiksa.',
                $nurse,
                $lockedQueue->patient_id,
            );
        }, attempts: 3);
    }
}
