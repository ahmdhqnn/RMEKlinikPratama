<?php

namespace App\Repositories\Eloquent;

use App\Models\DowntimeReconciliation;
use App\Repositories\Contracts\DowntimeReconciliationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentDowntimeReconciliationRepository implements DowntimeReconciliationRepositoryInterface
{
    public function paginateForReview(string $filter, int $perPage = 20): LengthAwarePaginator
    {
        $query = DowntimeReconciliation::query()
            ->with([
                'medicalRecord:id,patient_id,queue_id,doctor_id,status,signed_at',
                'medicalRecord.patient:id,no_rm,nama_lengkap',
                'medicalRecord.queue:id,tanggal_antrean,poli',
                'medicalRecord.doctor:id,name',
                'enteredBy:id,name,role',
                'reviewedBy:id,name,role',
            ]);

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'pending_review' THEN 0 WHEN status = 'discrepancy' THEN 1 ELSE 2 END")
            ->orderByDesc('service_occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @param array<int, int> $reconciliationIds */
    public function eventsForReconciliations(array $reconciliationIds): Collection
    {
        if ($reconciliationIds === []) {
            return collect();
        }

        return DB::table('downtime_reconciliation_events')
            ->whereIn('downtime_reconciliation_id', $reconciliationIds)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get()
            ->groupBy('downtime_reconciliation_id');
    }

    public function findForUpdate(DowntimeReconciliation $reconciliation): DowntimeReconciliation
    {
        return DowntimeReconciliation::query()
            ->whereKey($reconciliation->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
