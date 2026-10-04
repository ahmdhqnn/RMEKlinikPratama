<?php

namespace App\Services;

use App\Models\DowntimeReconciliation;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Repositories\Contracts\DowntimeReconciliationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DowntimeReconciliationService
{
    public function __construct(
        private DowntimeReconciliationRepositoryInterface $reconciliations,
        private AuditLogService $auditLogService,
    ) {}

    public function paginateForReview(string $filter): LengthAwarePaginator
    {
        return $this->reconciliations->paginateForReview($filter);
    }

    /** @param array<int, int> $reconciliationIds */
    public function eventsForReconciliations(array $reconciliationIds): Collection
    {
        return $this->reconciliations->eventsForReconciliations($reconciliationIds);
    }

    public function createFromDowntime(
        MedicalRecord $medicalRecord,
        User $actor,
        string $paperReference,
        string $serviceOccurredAt,
    ): DowntimeReconciliation {
        if (! $actor->hasRole('dokter', 'dokter_gigi')
            || $medicalRecord->doctor_id !== $actor->id
            || $medicalRecord->status !== 'final') {
            abort(403);
        }

        $medicalRecord->loadMissing('queue');
        $expectedRole = $medicalRecord->queue?->poli === 'gigi' ? 'dokter_gigi' : 'dokter';
        if (! $actor->hasRole($expectedRole)) {
            abort(403);
        }

        if (now()->lt($serviceOccurredAt)) {
            throw ValidationException::withMessages([
                'service_occurred_at' => 'Waktu pelayanan downtime tidak boleh berada di masa depan.',
            ]);
        }

        $serviceOccurredAtUtc = Carbon::parse($serviceOccurredAt, config('app.timezone'))->utc();

        return DB::transaction(function () use ($medicalRecord, $actor, $paperReference, $serviceOccurredAtUtc): DowntimeReconciliation {
            $reconciliation = DowntimeReconciliation::query()->create([
                'medical_record_id' => $medicalRecord->id,
                'entered_by_user_id' => $actor->id,
                'paper_reference' => trim($paperReference),
                'service_occurred_at' => $serviceOccurredAtUtc,
                'status' => 'pending_review',
            ]);

            $this->appendEvent(
                $reconciliation,
                'created',
                null,
                'pending_review',
                $actor,
                'Metadata rekonsiliasi downtime dicatat; isi klinis dan waktu finalisasi sistem tidak diubah.',
            );

            $this->auditLogService->record(
                'CREATE_DOWNTIME_RECONCILIATION',
                'Metadata formulir downtime ditautkan ke catatan final.',
                $actor,
                $medicalRecord->patient_id,
                [
                    'downtime_reconciliation_id' => $reconciliation->id,
                    'medical_record_id' => $medicalRecord->id,
                    'status' => 'pending_review',
                ],
            );

            return $reconciliation;
        });
    }

    public function review(
        DowntimeReconciliation $reconciliation,
        User $reviewer,
        string $status,
        string $note,
    ): DowntimeReconciliation {
        abort_unless($reviewer->hasRole('perekam_medis'), 403);

        return DB::transaction(function () use ($reconciliation, $reviewer, $status, $note): DowntimeReconciliation {
            $locked = $this->reconciliations->findForUpdate($reconciliation);
            $fromStatus = $locked->status;
            $allowedStatuses = match ($fromStatus) {
                'pending_review' => ['reconciled', 'discrepancy'],
                'discrepancy' => ['reconciled'],
                default => [],
            };

            if (! in_array($status, $allowedStatuses, true)) {
                throw ValidationException::withMessages(['status' => 'Transisi rekonsiliasi downtime tidak diizinkan.']);
            }

            $reviewNote = trim($note);
            if ($reviewNote === '') {
                throw ValidationException::withMessages(['note' => 'Catatan hasil rekonsiliasi wajib diisi.']);
            }

            $locked->update([
                'status' => $status,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now('UTC'),
                'review_note' => $reviewNote,
            ]);

            $this->appendEvent($locked, 'reviewed', $fromStatus, $status, $reviewer, $reviewNote);

            $this->auditLogService->record(
                'REVIEW_DOWNTIME_RECONCILIATION',
                'Status rekonsiliasi downtime ditinjau PMIK.',
                $reviewer,
                $locked->medicalRecord->patient_id,
                [
                    'downtime_reconciliation_id' => $locked->id,
                    'medical_record_id' => $locked->medical_record_id,
                    'from_status' => $fromStatus,
                    'to_status' => $status,
                ],
            );

            return $locked->refresh();
        });
    }

    private function appendEvent(
        DowntimeReconciliation $reconciliation,
        string $eventType,
        ?string $fromStatus,
        string $toStatus,
        User $actor,
        string $note,
    ): void {
        DB::table('downtime_reconciliation_events')->insert([
            'downtime_reconciliation_id' => $reconciliation->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'event_note' => $note,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role,
            'recorded_at' => now('UTC'),
        ]);
    }
}
