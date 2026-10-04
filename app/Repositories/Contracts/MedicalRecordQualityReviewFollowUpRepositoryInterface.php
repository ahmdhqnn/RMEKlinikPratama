<?php

namespace App\Repositories\Contracts;

use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface MedicalRecordQualityReviewFollowUpRepositoryInterface
{
    public function paginateForUser(User $user, string $filter, int $perPage = 20): LengthAwarePaginator;

    /** @param array<int, int> $followUpIds */
    public function eventsForFollowUps(array $followUpIds): Collection;

    public function findForUpdate(MedicalRecordQualityReviewFollowUp $followUp): MedicalRecordQualityReviewFollowUp;
}
