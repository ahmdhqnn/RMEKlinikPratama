<?php

namespace App\Repositories\Eloquent;

use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Models\User;
use App\Repositories\Contracts\MedicalRecordQualityReviewFollowUpRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentMedicalRecordQualityReviewFollowUpRepository implements MedicalRecordQualityReviewFollowUpRepositoryInterface
{
    public function paginateForUser(User $user, string $filter, int $perPage = 20): LengthAwarePaginator
    {
        $query = MedicalRecordQualityReviewFollowUp::query()
            ->with([
                'assignee:id,name,role',
                'qualityReview:id,medical_record_id,reviewed_record_version,reviewed_at,reviewed_by_name',
                'qualityReview.medicalRecord:id,patient_id,queue_id,doctor_id',
                'qualityReview.medicalRecord.patient:id,no_rm,nama_lengkap',
                'qualityReview.medicalRecord.queue:id,tanggal_antrean,poli',
                'qualityReview.medicalRecord.doctor:id,name',
                'qualityReview.medicalRecord.revisions',
                'qualityReview.reviewer:id,name',
                'resolvedBy:id,name',
            ]);

        if (! $user->hasRole('perekam_medis')) {
            $query->where('assigned_to_user_id', $user->id);
        }

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return $query
            ->orderByRaw("CASE WHEN status IN ('resolved', 'not_applicable') THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @param array<int, int> $followUpIds */
    public function eventsForFollowUps(array $followUpIds): Collection
    {
        if ($followUpIds === []) {
            return collect();
        }

        return DB::table('medical_record_quality_review_follow_up_events')
            ->whereIn('medical_record_quality_review_follow_up_id', $followUpIds)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get()
            ->groupBy('medical_record_quality_review_follow_up_id');
    }

    public function findForUpdate(MedicalRecordQualityReviewFollowUp $followUp): MedicalRecordQualityReviewFollowUp
    {
        return MedicalRecordQualityReviewFollowUp::query()
            ->whereKey($followUp->id)
            ->lockForUpdate()
            ->with(['qualityReview.medicalRecord.queue', 'qualityReview.medicalRecord.revisions'])
            ->firstOrFail();
    }
}
