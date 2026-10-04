<?php

namespace App\Repositories\Contracts;

use App\Models\DowntimeReconciliation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DowntimeReconciliationRepositoryInterface
{
    public function paginateForReview(string $filter, int $perPage = 20): LengthAwarePaginator;

    /** @param array<int, int> $reconciliationIds */
    public function eventsForReconciliations(array $reconciliationIds): Collection;

    public function findForUpdate(DowntimeReconciliation $reconciliation): DowntimeReconciliation;
}
