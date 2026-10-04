<?php

namespace App\Repositories\Contracts;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\QualityReviewSamplingRun;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface MedicalRecordQualityReviewRepositoryInterface
{
    /** @return array{final_records: int, pending_reviews: int, follow_up_reviews: int, complete_reviews: int, open_follow_ups: int, overdue_follow_ups: int} */
    public function qualitySummary(): array;

    /** @return Collection<int, array{month: string, total_reviews: int, complete_reviews: int, follow_up_reviews: int}> */
    public function qualityTrends(int $months = 6): Collection;

    /** @return Collection<int, array{month: string, poli: string, total_reviews: int, complete_reviews: int, follow_up_reviews: int}> */
    public function qualityTrendsByPoli(int $months = 6): Collection;

    public function paginateFinalRecords(string $filter, string $search, int $perPage = 20): LengthAwarePaginator;

    public function findFinalRecord(MedicalRecord $medicalRecord): MedicalRecord;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): MedicalRecordQualityReview;

    /** @return array<string, int> */
    public function samplingPopulationCounts(Carbon $periodStart, Carbon $periodEnd): array;

    /** @return Collection<int, array{id: int, poli: string, is_high_risk: bool}> */
    public function samplingCandidates(Carbon $periodStart, Carbon $periodEnd, string $poli, bool $highRisk, string $seed, int $limit, array $excludeIds = []): Collection;

    /** @return Collection<int, QualityReviewSamplingRun> */
    public function recentSamplingRuns(int $limit = 12): Collection;
}
