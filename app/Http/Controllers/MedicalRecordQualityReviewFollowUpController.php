<?php

namespace App\Http\Controllers;

use App\Enums\MedicalRecordQualityReviewFollowUpStatus;
use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Services\AuditLogService;
use App\Services\MedicalRecordQualityReviewFollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicalRecordQualityReviewFollowUpController extends Controller
{
    public function index(Request $request, MedicalRecordQualityReviewFollowUpService $followUps, AuditLogService $auditLogService): View
    {
        Gate::authorize('viewAny', MedicalRecordQualityReviewFollowUp::class);
        $validated = $request->validate([
            'filter' => ['nullable', 'in:all,open,in_progress,resolved,not_applicable'],
        ]);
        $filter = $validated['filter'] ?? 'open';
        $user = $request->user();

        $auditLogService->record('VIEW_MEDICAL_RECORD_QUALITY_FOLLOW_UP_QUEUE', 'Membuka antrean tindak lanjut mutu.', $user, payload: [
            'filter' => $filter,
        ]);

        $tasks = $followUps->paginateForUser($user, $filter);

        return view('clinic.quality_reviews.follow_ups', [
            'currentUser' => $user,
            'tasks' => $tasks,
            'filter' => $filter,
            'events' => $followUps->eventsForFollowUps($tasks->getCollection()->pluck('id')->all()),
        ]);
    }

    public function update(Request $request, MedicalRecordQualityReviewFollowUp $followUp, MedicalRecordQualityReviewFollowUpService $followUps): RedirectResponse
    {
        Gate::authorize('update', $followUp);
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                MedicalRecordQualityReviewFollowUpStatus::InProgress->value,
                MedicalRecordQualityReviewFollowUpStatus::Resolved->value,
                MedicalRecordQualityReviewFollowUpStatus::NotApplicable->value,
            ])],
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $followUps->transition(
            $followUp,
            $request->user(),
            MedicalRecordQualityReviewFollowUpStatus::from($validated['status']),
            $validated['note'],
        );

        return redirect()
            ->route('quality-review-follow-ups.index')
            ->with('success', 'Tindak lanjut dan jejak status berhasil dicatat.');
    }
}
