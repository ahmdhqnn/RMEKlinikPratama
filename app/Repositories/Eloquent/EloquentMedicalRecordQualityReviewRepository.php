<?php

namespace App\Repositories\Eloquent;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\QualityReviewSamplingRun;
use App\Repositories\Contracts\MedicalRecordQualityReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentMedicalRecordQualityReviewRepository implements MedicalRecordQualityReviewRepositoryInterface
{
    /** @return array{final_records: int, pending_reviews: int, follow_up_reviews: int, complete_reviews: int, open_follow_ups: int, overdue_follow_ups: int} */
    public function qualitySummary(): array
    {
        $records = $this->recordsWithLatestReviewState();

        return [
            'final_records' => (clone $records)->count(),
            'pending_reviews' => (clone $records)->where(function (Builder $query): void {
                $query->whereNull('quality_reviews.id')
                    ->orWhereRaw('quality_reviews.reviewed_record_version < COALESCE(latest_versions.latest_version, 0)');
            })->count(),
            'follow_up_reviews' => (clone $records)->where('quality_reviews.review_result', 'needs_follow_up')
                ->whereRaw('quality_reviews.reviewed_record_version >= COALESCE(latest_versions.latest_version, 0)')
                ->count(),
            'complete_reviews' => (clone $records)->where('quality_reviews.review_result', 'complete')
                ->whereRaw('quality_reviews.reviewed_record_version >= COALESCE(latest_versions.latest_version, 0)')
                ->count(),
            'open_follow_ups' => DB::table('medical_record_quality_review_follow_ups')
                ->whereIn('status', ['open', 'in_progress'])
                ->count(),
            'overdue_follow_ups' => DB::table('medical_record_quality_review_follow_ups')
                ->whereIn('status', ['open', 'in_progress'])
                ->whereDate('due_at', '<', today())
                ->count(),
        ];
    }

    /** @return Collection<int, array{month: string, total_reviews: int, complete_reviews: int, follow_up_reviews: int}> */
    public function qualityTrends(int $months = 6): Collection
    {
        $months = max(1, min($months, 24));
        $startOfFirstMonth = Carbon::now(config('app.timezone'))->startOfMonth()->subMonths($months - 1);
        $rows = DB::table('medical_record_quality_reviews')
            ->selectRaw("to_char(reviewed_at AT TIME ZONE ?, 'YYYY-MM') AS month", [config('app.timezone')])
            ->selectRaw("COUNT(*) AS total_reviews, COUNT(*) FILTER (WHERE review_result = 'complete') AS complete_reviews, COUNT(*) FILTER (WHERE review_result = 'needs_follow_up') AS follow_up_reviews")
            ->where('reviewed_at', '>=', $startOfFirstMonth)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($startOfFirstMonth, $rows): array {
                $month = $startOfFirstMonth->copy()->addMonths($offset)->format('Y-m');
                $row = $rows->get($month);

                return [
                    'month' => $month,
                    'total_reviews' => (int) ($row->total_reviews ?? 0),
                    'complete_reviews' => (int) ($row->complete_reviews ?? 0),
                    'follow_up_reviews' => (int) ($row->follow_up_reviews ?? 0),
                ];
            });
    }

    public function qualityTrendsByPoli(int $months = 6): Collection
    {
        $months = max(1, min($months, 24));
        $startOfFirstMonth = Carbon::now(config('app.timezone'))->startOfMonth()->subMonths($months - 1);
        $rows = DB::table('medical_record_quality_reviews')
            ->join('medical_records', 'medical_records.id', '=', 'medical_record_quality_reviews.medical_record_id')
            ->join('queues', 'queues.id', '=', 'medical_records.queue_id')
            ->selectRaw("to_char(medical_record_quality_reviews.reviewed_at AT TIME ZONE ?, 'YYYY-MM') AS month", [config('app.timezone')])
            ->addSelect('queues.poli')
            ->selectRaw("COUNT(*) AS total_reviews, COUNT(*) FILTER (WHERE review_result = 'complete') AS complete_reviews, COUNT(*) FILTER (WHERE review_result = 'needs_follow_up') AS follow_up_reviews")
            ->where('medical_record_quality_reviews.reviewed_at', '>=', $startOfFirstMonth)
            ->whereIn('queues.poli', ['umum', 'gigi'])
            ->groupBy('month', 'queues.poli')
            ->orderBy('month')
            ->orderBy('queues.poli')
            ->get()
            ->keyBy(static fn (object $row): string => $row->month.'|'.$row->poli);

        return collect(range(0, $months - 1))
            ->flatMap(function (int $offset) use ($startOfFirstMonth, $rows): array {
                $month = $startOfFirstMonth->copy()->addMonths($offset)->format('Y-m');

                return collect(['umum', 'gigi'])->map(static function (string $poli) use ($month, $rows): array {
                    $row = $rows->get($month.'|'.$poli);

                    return [
                        'month' => $month,
                        'poli' => $poli,
                        'total_reviews' => (int) ($row->total_reviews ?? 0),
                        'complete_reviews' => (int) ($row->complete_reviews ?? 0),
                        'follow_up_reviews' => (int) ($row->follow_up_reviews ?? 0),
                    ];
                })->all();
            });
    }

    public function paginateFinalRecords(string $filter, string $search, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->recordsWithLatestReviewState()
            ->select([
                'medical_records.*',
                'quality_reviews.review_result as latest_quality_review_result',
                'quality_reviews.reviewed_at as latest_quality_reviewed_at',
                'quality_reviews.reviewed_record_version as latest_reviewed_record_version',
                DB::raw('COALESCE(latest_versions.latest_version, 0) AS current_record_version'),
            ])
            ->with([
                'patient:id,no_rm,nama_lengkap,identity_status',
                'doctor:id,name',
                'queue:id,tanggal_antrean,poli',
            ]);

        if ($filter === 'pending') {
            $query->where(function ($builder): void {
                $builder->whereNull('quality_reviews.id')
                    ->orWhereRaw('quality_reviews.reviewed_record_version < COALESCE(latest_versions.latest_version, 0)');
            });
        } elseif ($filter === 'needs_follow_up') {
            $query->where('quality_reviews.review_result', 'needs_follow_up');
        } elseif ($filter === 'complete') {
            $query->where('quality_reviews.review_result', 'complete')
                ->whereRaw('quality_reviews.reviewed_record_version >= COALESCE(latest_versions.latest_version, 0)');
        }

        if ($search !== '') {
            $query->whereHas('patient', function ($patientQuery) use ($search): void {
                $patientQuery->where('no_rm', 'ILIKE', "%{$search}%")
                    ->orWhere('nama_lengkap', 'ILIKE', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('medical_records.created_at')
            ->orderByDesc('medical_records.id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function recordsWithLatestReviewState(): Builder
    {
        $latestReviewRounds = DB::table('medical_record_quality_reviews')
            ->select('medical_record_id')
            ->selectRaw('MAX(review_round) AS latest_round')
            ->groupBy('medical_record_id');

        $latestRecordVersions = DB::table('medical_record_revisions')
            ->select('medical_record_id')
            ->selectRaw('MAX(version_number) AS latest_version')
            ->groupBy('medical_record_id');

        return MedicalRecord::query()
            ->leftJoinSub($latestReviewRounds, 'latest_rounds', function (JoinClause $join): void {
                $join->on('latest_rounds.medical_record_id', '=', 'medical_records.id');
            })
            ->leftJoin('medical_record_quality_reviews as quality_reviews', function (JoinClause $join): void {
                $join->on('quality_reviews.medical_record_id', '=', 'medical_records.id')
                    ->on('quality_reviews.review_round', '=', 'latest_rounds.latest_round');
            })
            ->leftJoinSub($latestRecordVersions, 'latest_versions', function (JoinClause $join): void {
                $join->on('latest_versions.medical_record_id', '=', 'medical_records.id');
            })
            ->where('medical_records.status', 'final');
    }

    public function findFinalRecord(MedicalRecord $medicalRecord): MedicalRecord
    {
        return MedicalRecord::query()
            ->whereKey($medicalRecord->id)
            ->where('status', 'final')
            ->with([
                'patient',
                'doctor',
                'queue.vitalSign.nurse',
                'odontogram.dentist',
                'letters',
                'revisions',
                'qualityReviews',
                'qualityReviews.followUps.assignee',
                'qualityReviews.followUps.resolvedBy',
            ])
            ->firstOrFail();
    }

    public function create(array $attributes): MedicalRecordQualityReview
    {
        return MedicalRecordQualityReview::query()->create($attributes);
    }

    public function samplingPopulationCounts(Carbon $periodStart, Carbon $periodEnd): array
    {
        return MedicalRecord::query()
            ->join('queues', 'queues.id', '=', 'medical_records.queue_id')
            ->where('medical_records.status', 'final')
            ->whereBetween('medical_records.signed_at', [$periodStart, $periodEnd])
            ->selectRaw('queues.poli, COUNT(*) AS aggregate_count')
            ->groupBy('queues.poli')
            ->pluck('aggregate_count', 'poli')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }

    public function samplingCandidates(Carbon $periodStart, Carbon $periodEnd, string $poli, bool $highRisk, string $seed, int $limit, array $excludeIds = []): Collection
    {
        $latestReviews = DB::table('medical_record_quality_reviews')
            ->select('medical_record_id')
            ->selectRaw('MAX(review_round) AS latest_round')
            ->groupBy('medical_record_id');
        $latestVersions = DB::table('medical_record_revisions')
            ->select('medical_record_id')
            ->selectRaw('MAX(version_number) AS latest_version')
            ->groupBy('medical_record_id');

        return MedicalRecord::query()
            ->join('queues', 'queues.id', '=', 'medical_records.queue_id')
            ->leftJoinSub($latestReviews, 'sampling_latest_rounds', function (JoinClause $join): void {
                $join->on('sampling_latest_rounds.medical_record_id', '=', 'medical_records.id');
            })
            ->leftJoin('medical_record_quality_reviews as sampling_latest_review', function (JoinClause $join): void {
                $join->on('sampling_latest_review.medical_record_id', '=', 'medical_records.id')
                    ->on('sampling_latest_review.review_round', '=', 'sampling_latest_rounds.latest_round');
            })
            ->leftJoinSub($latestVersions, 'sampling_latest_versions', function (JoinClause $join): void {
                $join->on('sampling_latest_versions.medical_record_id', '=', 'medical_records.id');
            })
            ->where('medical_records.status', 'final')
            ->whereBetween('medical_records.signed_at', [$periodStart, $periodEnd])
            ->where('queues.poli', $poli)
            ->whereRaw("(sampling_latest_review.id IS NULL OR sampling_latest_review.reviewed_record_version < COALESCE(sampling_latest_versions.latest_version, 0) OR sampling_latest_review.review_result = 'needs_follow_up') = ?", [$highRisk])
            ->when($excludeIds !== [], fn (Builder $query): Builder => $query->whereNotIn('medical_records.id', $excludeIds))
            ->orderByRaw('MD5(CAST(medical_records.id AS text) || ?)', [$seed])
            ->limit($limit)
            ->get(['medical_records.id', 'queues.poli'])
            ->map(static fn (MedicalRecord $record): array => [
                'id' => $record->id,
                'poli' => $record->poli,
                'is_high_risk' => $highRisk,
            ]);
    }

    public function recentSamplingRuns(int $limit = 12): Collection
    {
        return QualityReviewSamplingRun::query()
            ->with(['createdBy:id,name', 'items.medicalRecord.patient:id,no_rm,nama_lengkap', 'items.medicalRecord.queue:id,poli'])
            ->orderByDesc('period_start')
            ->limit($limit)
            ->get();
    }
}
