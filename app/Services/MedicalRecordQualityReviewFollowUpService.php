<?php

namespace App\Services;

use App\Enums\MedicalRecordQualityReviewFollowUpStatus;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Models\User;
use App\Repositories\Contracts\MedicalRecordQualityReviewFollowUpRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicalRecordQualityReviewFollowUpService
{
    public function __construct(
        private MedicalRecordQualityReviewFollowUpRepositoryInterface $followUps,
        private AuditLogService $auditLogService,
    ) {}

    public function paginateForUser(User $user, string $filter): LengthAwarePaginator
    {
        return $this->followUps->paginateForUser($user, $filter);
    }

    /** @param array<int, int> $followUpIds */
    public function eventsForFollowUps(array $followUpIds): Collection
    {
        return $this->followUps->eventsForFollowUps($followUpIds);
    }

    /** @param array<string, string> $findingNotes
     * @param  array<string, string>  $itemLabels
     */
    public function createTasksForReview(
        MedicalRecord $medicalRecord,
        MedicalRecordQualityReview $review,
        User $reviewer,
        array $findingNotes,
        array $itemLabels,
        string $dueAt,
    ): void {
        $medicalRecord->loadMissing(['queue', 'doctor']);
        $expectedRole = $medicalRecord->queue?->poli === 'gigi' ? 'dokter_gigi' : 'dokter';
        $assignee = $medicalRecord->doctor;

        if ($assignee === null || ! $assignee->is_active || ! $assignee->hasRole($expectedRole)) {
            throw ValidationException::withMessages([
                'review_result' => 'Dokter penulis catatan tidak aktif atau role-nya tidak sesuai; temuan belum dapat ditugaskan.',
            ]);
        }

        if (now()->startOfDay()->gt(Carbon::parse($dueAt)->startOfDay())) {
            throw ValidationException::withMessages(['follow_up_due_at' => 'Tenggat tindak lanjut harus hari ini atau setelahnya.']);
        }

        foreach ($findingNotes as $itemKey => $finding) {
            if (! isset($itemLabels[$itemKey]) || trim($finding) === '') {
                throw ValidationException::withMessages(['finding_notes' => 'Setiap item yang ditandai temuan wajib memiliki uraian yang jelas.']);
            }

            $followUp = MedicalRecordQualityReviewFollowUp::query()->create([
                'medical_record_quality_review_id' => $review->id,
                'item_key' => $itemKey,
                'item_label' => $itemLabels[$itemKey],
                'finding' => trim($finding),
                'assigned_to_user_id' => $assignee->id,
                'due_at' => $dueAt,
                'status' => MedicalRecordQualityReviewFollowUpStatus::Open,
            ]);

            $this->appendEvent(
                $followUp,
                'created',
                null,
                MedicalRecordQualityReviewFollowUpStatus::Open,
                $reviewer,
                'Tindak lanjut dibuat dari review mutu.',
                ['item_label' => $itemLabels[$itemKey], 'assigned_to_user_id' => $assignee->id, 'due_at' => $dueAt],
            );

            $this->auditLogService->record(
                'CREATE_MEDICAL_RECORD_QUALITY_FOLLOW_UP',
                'Tindak lanjut temuan mutu ditugaskan kepada penulis catatan.',
                $reviewer,
                $medicalRecord->patient_id,
                [
                    'medical_record_id' => $medicalRecord->id,
                    'quality_review_id' => $review->id,
                    'follow_up_id' => $followUp->id,
                    'item_key' => $itemKey,
                    'assigned_to_user_id' => $assignee->id,
                ],
            );
        }
    }

    public function transition(
        MedicalRecordQualityReviewFollowUp $followUp,
        User $actor,
        MedicalRecordQualityReviewFollowUpStatus $toStatus,
        string $note,
    ): MedicalRecordQualityReviewFollowUp {
        return DB::transaction(function () use ($followUp, $actor, $toStatus, $note): MedicalRecordQualityReviewFollowUp {
            $lockedFollowUp = $this->followUps->findForUpdate($followUp);
            $medicalRecord = MedicalRecord::query()
                ->whereKey($lockedFollowUp->qualityReview->medical_record_id)
                ->lockForUpdate()
                ->with('queue')
                ->firstOrFail();
            $expectedRole = $medicalRecord->queue?->poli === 'gigi' ? 'dokter_gigi' : 'dokter';

            if ($lockedFollowUp->assigned_to_user_id !== $actor->id || ! $actor->hasRole($expectedRole)) {
                abort(403);
            }

            $fromStatus = $lockedFollowUp->status;
            $allowedTargets = match ($fromStatus) {
                MedicalRecordQualityReviewFollowUpStatus::Open => [
                    MedicalRecordQualityReviewFollowUpStatus::InProgress,
                    MedicalRecordQualityReviewFollowUpStatus::Resolved,
                    MedicalRecordQualityReviewFollowUpStatus::NotApplicable,
                ],
                MedicalRecordQualityReviewFollowUpStatus::InProgress => [
                    MedicalRecordQualityReviewFollowUpStatus::Resolved,
                    MedicalRecordQualityReviewFollowUpStatus::NotApplicable,
                ],
                default => [],
            };

            if (! in_array($toStatus, $allowedTargets, true)) {
                throw ValidationException::withMessages(['status' => 'Transisi status tindak lanjut tidak diizinkan.']);
            }

            $resolutionNote = trim($note);
            if ($resolutionNote === '') {
                throw ValidationException::withMessages(['note' => 'Catatan wajib diisi untuk mencatat perubahan status.']);
            }

            $recordVersion = null;
            if ($toStatus === MedicalRecordQualityReviewFollowUpStatus::Resolved) {
                $recordVersion = (int) $medicalRecord->revisions()->max('version_number');
                if ($recordVersion <= $lockedFollowUp->qualityReview->reviewed_record_version) {
                    throw ValidationException::withMessages([
                        'status' => 'Buat koreksi atau addendum klinis terlebih dahulu sebelum menutup temuan sebagai selesai.',
                    ]);
                }
            }

            $lockedFollowUp->status = $toStatus;
            $lockedFollowUp->resolution_note = $toStatus->isResolved() ? $resolutionNote : null;
            $lockedFollowUp->resolved_by_user_id = $toStatus->isResolved() ? $actor->id : null;
            $lockedFollowUp->resolved_at = $toStatus->isResolved() ? now() : null;
            $lockedFollowUp->resolved_record_version = $recordVersion;
            $lockedFollowUp->save();

            $this->appendEvent(
                $lockedFollowUp,
                'status_changed',
                $fromStatus,
                $toStatus,
                $actor,
                $resolutionNote,
                ['resolved_record_version' => $recordVersion],
            );

            $this->auditLogService->record(
                'UPDATE_MEDICAL_RECORD_QUALITY_FOLLOW_UP',
                'Status tindak lanjut temuan mutu diperbarui.',
                $actor,
                $medicalRecord->patient_id,
                [
                    'medical_record_id' => $medicalRecord->id,
                    'follow_up_id' => $lockedFollowUp->id,
                    'from_status' => $fromStatus->value,
                    'to_status' => $toStatus->value,
                    'resolved_record_version' => $recordVersion,
                ],
            );

            return $lockedFollowUp;
        });
    }

    /** @param array<string, mixed>|null $eventData */
    private function appendEvent(
        MedicalRecordQualityReviewFollowUp $followUp,
        string $eventType,
        ?MedicalRecordQualityReviewFollowUpStatus $fromStatus,
        MedicalRecordQualityReviewFollowUpStatus $toStatus,
        User $actor,
        string $note,
        ?array $eventData = null,
    ): void {
        DB::table('medical_record_quality_review_follow_up_events')->insert([
            'medical_record_quality_review_follow_up_id' => $followUp->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'event_data' => $eventData === null ? null : json_encode($eventData, JSON_THROW_ON_ERROR),
            'event_note' => $note,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role,
            'recorded_at' => now(),
        ]);
    }
}
