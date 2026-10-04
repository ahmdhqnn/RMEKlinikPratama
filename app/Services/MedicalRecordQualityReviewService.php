<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\QualityReviewSamplingRun;
use App\Models\User;
use App\Repositories\Contracts\MedicalRecordQualityReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MedicalRecordQualityReviewService
{
    public function __construct(
        private MedicalRecordQualityReviewRepositoryInterface $reviews,
        private MedicalRecordQualityReviewFollowUpService $followUpService,
        private AuditLogService $auditLogService,
    ) {}

    public function paginate(string $filter, string $search): LengthAwarePaginator
    {
        return $this->reviews->paginateFinalRecords($filter, $search);
    }

    /** @return array{final_records: int, pending_reviews: int, follow_up_reviews: int, complete_reviews: int, open_follow_ups: int, overdue_follow_ups: int} */
    public function qualitySummary(): array
    {
        return $this->reviews->qualitySummary();
    }

    /** @return Collection<int, array{month: string, total_reviews: int, complete_reviews: int, follow_up_reviews: int}> */
    public function qualityTrends(int $months = 6): Collection
    {
        return $this->reviews->qualityTrends($months);
    }

    /** @return Collection<int, array{month: string, poli: string, total_reviews: int, complete_reviews: int, follow_up_reviews: int}> */
    public function qualityTrendsByPoli(int $months = 6): Collection
    {
        return $this->reviews->qualityTrendsByPoli($months);
    }

    /** @return Collection<int, QualityReviewSamplingRun> */
    public function recentSamplingRuns(): Collection
    {
        return $this->reviews->recentSamplingRuns();
    }

    public function createMonthlySamplingRun(string $period, int $sampleSize, User $actor): QualityReviewSamplingRun
    {
        abort_unless($actor->hasRole('perekam_medis'), 403);

        $periodStart = Carbon::createFromFormat('Y-m-d', $period.'-01', config('app.timezone'))->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $populationCounts = $this->reviews->samplingPopulationCounts($periodStart, $periodEnd);
        $populationCounts = array_intersect_key($populationCounts, ['umum' => true, 'gigi' => true]);
        $totalPopulation = array_sum($populationCounts);

        if ($totalPopulation === 0) {
            throw ValidationException::withMessages(['period' => 'Tidak ada catatan final pada periode dan poli yang dipilih.']);
        }

        if (QualityReviewSamplingRun::query()->whereDate('period_start', $periodStart)->exists()) {
            throw ValidationException::withMessages(['period' => 'Cohort sampling untuk bulan ini sudah dibuat. Gunakan cohort yang ada agar jejak pemilihan tetap konsisten.']);
        }

        $selectedTotal = min($sampleSize, $totalPopulation);
        $quotas = array_fill_keys(array_keys($populationCounts), 0);
        $remaining = $selectedTotal;
        while ($remaining > 0) {
            $allocatedInPass = false;
            foreach (['umum', 'gigi'] as $poli) {
                if (isset($populationCounts[$poli]) && $quotas[$poli] < $populationCounts[$poli]) {
                    $quotas[$poli]++;
                    $remaining--;
                    $allocatedInPass = true;
                    if ($remaining === 0) {
                        break;
                    }
                }
            }
            if (! $allocatedInPass) {
                break;
            }
        }

        $seed = Str::random(48);
        $selectedItems = [];
        foreach ($quotas as $poli => $quota) {
            if ($quota === 0) {
                continue;
            }

            $highRiskTarget = (int) ceil($quota / 2);
            $highRiskItems = $this->reviews->samplingCandidates($periodStart, $periodEnd, $poli, true, $seed, $highRiskTarget);
            $routineItems = $this->reviews->samplingCandidates($periodStart, $periodEnd, $poli, false, $seed, $quota - $highRiskItems->count());
            $selectedItems = [...$selectedItems, ...$highRiskItems->all(), ...$routineItems->all()];
            $additionalRiskCount = $quota - $highRiskItems->count() - $routineItems->count();

            if ($additionalRiskCount > 0) {
                $selectedIds = array_column($selectedItems, 'id');
                $additionalRiskItems = $this->reviews->samplingCandidates($periodStart, $periodEnd, $poli, true, $seed, $additionalRiskCount, $selectedIds);
                $selectedItems = [...$selectedItems, ...$additionalRiskItems->all()];
            }
        }

        if ($selectedItems === []) {
            throw ValidationException::withMessages(['period' => 'Sampel tidak dapat dibentuk dari populasi yang tersedia.']);
        }

        return DB::transaction(function () use ($actor, $periodStart, $periodEnd, $sampleSize, $seed, $selectedItems, $populationCounts): QualityReviewSamplingRun {
            $run = QualityReviewSamplingRun::query()->create([
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'requested_sample_size' => $sampleSize,
                'selected_sample_size' => count($selectedItems),
                'population_counts_json' => $populationCounts,
                'selection_seed' => $seed,
                'sampling_method' => 'stratified_risk_and_routine',
                'created_by_user_id' => $actor->id,
                'created_by_name' => $actor->name,
                'created_by_role' => $actor->role,
                'created_at' => now('UTC'),
            ]);

            $run->items()->createMany(array_map(static fn (array $item): array => [
                'medical_record_id' => $item['id'],
                'poli' => $item['poli'],
                'selection_reason' => $item['is_high_risk'] ? 'high_risk' : 'routine',
                'selected_at' => now('UTC'),
            ], $selectedItems));

            $this->auditLogService->record(
                'CREATE_MEDICAL_RECORD_QUALITY_SAMPLING_RUN',
                'Cohort sampling mutu bulanan dibentuk untuk review PMIK.',
                $actor,
                payload: [
                    'sampling_run_id' => $run->id,
                    'period_start' => $periodStart->toDateString(),
                    'selected_count' => count($selectedItems),
                    'population_counts' => $populationCounts,
                ],
            );

            return $run->load(['items.medicalRecord.patient:id,no_rm,nama_lengkap', 'items.medicalRecord.queue:id,poli']);
        });
    }

    public function findFinalRecord(MedicalRecord $medicalRecord): MedicalRecord
    {
        return $this->reviews->findFinalRecord($medicalRecord);
    }

    /** @return array<string, string> */
    public function checklistItems(MedicalRecord $medicalRecord): array
    {
        $items = [
            'patient_identity' => 'Identitas pasien atau pengecualian identitas darurat tercatat',
            'encounter_context' => 'Konteks kunjungan, tanggal, poli, dan tenaga kesehatan jelas',
            'soap_sections' => 'Catatan subjektif, objektif, asesmen, dan rencana terdokumentasi',
            'coded_diagnosis' => 'Diagnosis dan kode ICD-10 utama terdokumentasi',
            'signer_metadata' => 'Penanggung jawab dan waktu finalisasi tercatat',
            'disposition' => 'Disposisi akhir dan tindak lanjut terdokumentasi',
        ];

        if ($medicalRecord->queue?->poli === 'gigi') {
            $items['dental_documentation'] = 'Dokumentasi pemeriksaan/odontogram gigi tersedia atau alasan tidak dilakukan tercatat';
        }

        if ($medicalRecord->disposition === 'referral') {
            $items['referral_document'] = 'Dokumen dan informasi rujukan tersedia';
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function submit(MedicalRecord $medicalRecord, User $reviewer, array $data): MedicalRecordQualityReview
    {
        return DB::transaction(function () use ($medicalRecord, $reviewer, $data): MedicalRecordQualityReview {
            $lockedRecord = MedicalRecord::query()
                ->whereKey($medicalRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $reviewer->hasRole('perekam_medis') || $lockedRecord->status !== 'final') {
                abort(403);
            }

            $lockedRecord->load(['queue', 'patient', 'doctor']);
            $items = $this->checklistItems($lockedRecord);
            $checklist = $data['checklist'] ?? [];

            if (array_diff(array_keys($items), array_keys($checklist)) !== [] || array_diff(array_keys($checklist), array_keys($items)) !== []) {
                throw ValidationException::withMessages([
                    'checklist' => 'Daftar checklist berubah. Muat ulang halaman dan periksa kembali seluruh item.',
                ]);
            }

            foreach ($checklist as $value) {
                if (! in_array($value, ['pass', 'finding'], true)) {
                    throw ValidationException::withMessages(['checklist' => 'Pilih hasil untuk setiap item checklist.']);
                }
            }

            $hasFindings = in_array('finding', $checklist, true);
            $result = $data['review_result'] ?? null;
            $findingNotes = $data['finding_notes'] ?? [];
            $findingKeys = array_keys(array_filter($checklist, static fn (string $value): bool => $value === 'finding'));

            if (($result === 'complete' && ($hasFindings || $findingNotes !== [] || isset($data['follow_up_due_at'])))
                || ($result === 'needs_follow_up' && (! $hasFindings || $findingNotes === [] || ! isset($data['follow_up_due_at'])))
                || ! in_array($result, ['complete', 'needs_follow_up'], true)) {
                throw ValidationException::withMessages([
                    'review_result' => 'Hasil harus sesuai dengan checklist; setiap temuan harus memiliki uraian dan tenggat tindak lanjut.',
                ]);
            }

            if (array_diff($findingKeys, array_keys($findingNotes)) !== [] || array_diff(array_keys($findingNotes), $findingKeys) !== []) {
                throw ValidationException::withMessages(['finding_notes' => 'Uraian tindak lanjut harus sesuai dengan item yang ditandai sebagai temuan.']);
            }

            foreach ($findingNotes as $finding) {
                if (! is_string($finding) || trim($finding) === '') {
                    throw ValidationException::withMessages(['finding_notes' => 'Setiap temuan wajib memiliki uraian yang jelas.']);
                }
            }

            $findings = collect($findingNotes)
                ->map(fn (string $finding, string $key): string => $items[$key].': '.trim($finding))
                ->implode("\n");

            $currentVersion = (int) $lockedRecord->revisions()->max('version_number');
            $reviewRound = ((int) $lockedRecord->qualityReviews()->max('review_round')) + 1;
            $review = $this->reviews->create([
                'medical_record_id' => $lockedRecord->id,
                'review_round' => $reviewRound,
                'reviewed_record_version' => $currentVersion,
                'review_result' => $result,
                'checklist_json' => $checklist,
                'findings' => $findings !== '' ? $findings : null,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_by_name' => $reviewer->name,
                'reviewed_by_role' => $reviewer->role,
                'reviewed_at' => now(),
            ]);

            if ($result === 'needs_follow_up') {
                $this->followUpService->createTasksForReview(
                    $lockedRecord,
                    $review,
                    $reviewer,
                    $findingNotes,
                    $items,
                    $data['follow_up_due_at'],
                );
            }

            $this->auditLogService->record(
                'CREATE_MEDICAL_RECORD_QUALITY_REVIEW',
                'Review kelengkapan rekam medis final dicatat.',
                $reviewer,
                $lockedRecord->patient_id,
                [
                    'medical_record_id' => $lockedRecord->id,
                    'review_round' => $reviewRound,
                    'reviewed_record_version' => $currentVersion,
                    'review_result' => $result,
                ],
            );

            return $review;
        });
    }
}
