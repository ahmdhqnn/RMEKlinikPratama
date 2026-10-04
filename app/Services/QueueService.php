<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\User;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueService
{
    public function __construct(
        private QueueRepositoryInterface $queues,
        private PatientService $patients,
        private AuditLogService $auditLogService,
        private QueueLifecycleService $lifecycle,
    ) {}

    /** @param array<string, mixed> $data */
    public function register(array $data, User $actor): Queue
    {
        abort_unless($actor->hasRole('pendaftaran'), 403);

        return DB::transaction(function () use ($data, $actor): Queue {
            $patient = $this->patients->register($data);
            $poli = $data['poli'];
            $doctor = $this->queues->assignedDoctor($poli);
            $date = Carbon::today();

            $queue = $this->queues->create([
                'nomor_antrean' => $this->queues->nextNumber($date, $poli),
                'patient_id' => $patient->id,
                'poli' => $poli,
                'dokter_id' => $doctor?->id,
                'tanggal_antrean' => $date,
                'waktu_daftar' => now()->format('H:i:s'),
                'keluhan_singkat' => $data['keluhan_singkat'] ?? null,
                'status' => QueueStatus::WaitingForTriage->value,
                'prioritas' => ($data['identitas_tidak_diketahui'] ?? false) ? 'urgent' : 'normal',
                'is_emergency' => (bool) ($data['identitas_tidak_diketahui'] ?? false),
            ]);
            $this->lifecycle->recordInitialStatus($queue, $actor);

            $this->auditLogService->record(
                'CREATE_QUEUE',
                "Registrasi antrean {$queue->nomor_antrean}.",
                $actor,
                $patient->id,
                payload: [
                    'queue_id' => $queue->id,
                    'is_emergency' => $queue->is_emergency,
                    'identity_status' => $patient->identity_status,
                    'queue_status' => $queue->status,
                ],
            );

            return $queue->load(['patient', 'doctor']);
        }, attempts: 3);
    }

    public function call(Queue $queue, User $actor): Queue
    {
        return DB::transaction(function () use ($queue, $actor): Queue {
            $lockedQueue = $this->queues->lock($queue);

            if ($lockedQueue->tanggal_antrean?->isToday() !== true || $lockedQueue->status !== QueueStatus::ReadyForExamination->value) {
                throw ValidationException::withMessages([
                    'queue' => 'Antrean hanya dapat dipanggil setelah triase selesai.',
                ]);
            }

            $expectedDoctorRole = $lockedQueue->poli === 'gigi' ? 'dokter_gigi' : 'dokter';

            if (! $actor->hasRole($expectedDoctorRole)) {
                throw ValidationException::withMessages(['queue' => 'Dokter tidak memiliki kewenangan untuk poli antrean ini.']);
            }

            if ($lockedQueue->dokter_id !== null && $lockedQueue->dokter_id !== $actor->id) {
                throw ValidationException::withMessages(['queue' => 'Antrean ini telah ditetapkan kepada dokter lain.']);
            }

            $lockedQueue = $this->lifecycle->transition(
                $lockedQueue,
                QueueStatus::UnderExamination,
                $actor,
                'Dokter mulai melakukan pemeriksaan.',
                [
                    'dokter_id' => $lockedQueue->dokter_id ?? $actor->id,
                    'waktu_panggil' => now(),
                    'examined_by_user_id' => $actor->id,
                ],
            );

            $this->auditLogService->record(
                'CALL_PATIENT',
                "Antrean {$lockedQueue->nomor_antrean} dipanggil ke ruang pemeriksaan.",
                $actor,
                $lockedQueue->patient_id,
            );

            return $lockedQueue->load(['patient', 'doctor']);
        }, attempts: 3);
    }

    public function todayForDashboard(string $filter, string $search, User $user): LengthAwarePaginator
    {
        return $this->queues->todayForDashboard($filter, $search, $user);
    }

    public function queueForPrint(Queue $queue): Queue
    {
        return $this->queues->findQueueWithRelations($queue);
    }

    public function queuesForDailyReport(): Collection
    {
        return $this->queues->todayForReport();
    }
}
