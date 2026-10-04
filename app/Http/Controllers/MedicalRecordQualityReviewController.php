<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Services\AuditLogService;
use App\Services\MedicalRecordQualityReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicalRecordQualityReviewController extends Controller
{
    public function index(Request $request, MedicalRecordQualityReviewService $reviews, AuditLogService $auditLogService): View
    {
        Gate::authorize('viewAny', MedicalRecordQualityReview::class);
        $validated = $request->validate([
            'filter' => ['nullable', 'in:pending,needs_follow_up,complete,all'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $filter = $validated['filter'] ?? 'pending';
        $search = trim($validated['q'] ?? '');

        $auditLogService->record('VIEW_MEDICAL_RECORD_QUALITY_QUEUE', 'Membuka antrean review mutu rekam medis.', $request->user(), payload: [
            'filter' => $filter,
            'has_search' => $search !== '',
        ]);

        return view('clinic.quality_reviews.index', [
            'records' => $reviews->paginate($filter, $search),
            'summary' => $reviews->qualitySummary(),
            'trends' => $reviews->qualityTrends(),
            'trendsByPoli' => $reviews->qualityTrendsByPoli(),
            'samplingRuns' => $reviews->recentSamplingRuns(),
            'filter' => $filter,
            'search' => $search,
        ]);
    }

    public function createSamplingRun(Request $request, MedicalRecordQualityReviewService $reviews): RedirectResponse
    {
        Gate::authorize('create', MedicalRecordQualityReview::class);
        $validated = $request->validate([
            'period' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'sample_size' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $run = $reviews->createMonthlySamplingRun($validated['period'], (int) $validated['sample_size'], $request->user());

        return redirect()->route('quality-reviews.index')
            ->with('success', 'Cohort sampling terbentuk: '.$run->selected_sample_size.' catatan dari periode '.$run->period_start->format('F Y').'.');
    }

    public function show(Request $request, MedicalRecord $medicalRecord, MedicalRecordQualityReviewService $reviews, AuditLogService $auditLogService): View
    {
        Gate::authorize('qualityReview', $medicalRecord);
        $record = $reviews->findFinalRecord($medicalRecord);
        $auditLogService->record('VIEW_MEDICAL_RECORD_QUALITY_REVIEW', 'Membuka isi rekam medis untuk pemeriksaan kelengkapan.', $request->user(), $record->patient_id, [
            'medical_record_id' => $record->id,
        ]);
        $currentVersion = (int) $record->revisions->max('version_number');

        return view('clinic.quality_reviews.show', [
            'record' => $record,
            'checklistItems' => $reviews->checklistItems($record),
            'currentVersion' => $currentVersion,
            'followUpDueDefault' => today()->addDays(7)->toDateString(),
        ]);
    }

    public function store(Request $request, MedicalRecord $medicalRecord, MedicalRecordQualityReviewService $reviews): RedirectResponse
    {
        Gate::authorize('qualityReview', $medicalRecord);
        $record = $reviews->findFinalRecord($medicalRecord);
        $checklistItems = $reviews->checklistItems($record);
        $rules = [
            'review_result' => ['required', 'in:complete,needs_follow_up'],
            'checklist' => ['required', 'array'],
            'finding_notes' => ['sometimes', 'array'],
            'follow_up_due_at' => [
                Rule::requiredIf($request->input('review_result') === 'needs_follow_up'),
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'prohibited_unless:review_result,needs_follow_up',
            ],
        ];

        foreach (array_keys($checklistItems) as $key) {
            $rules['checklist.'.$key] = ['required', 'in:pass,finding'];
            $rules['finding_notes.'.$key] = [
                Rule::requiredIf($request->input('checklist.'.$key) === 'finding'),
                'nullable',
                'string',
                'max:3000',
                'prohibited_unless:review_result,needs_follow_up',
            ];
        }

        $validated = $request->validate($rules);
        $reviews->submit($record, $request->user(), $validated);

        return redirect()
            ->route('quality-reviews.show', $record)
            ->with('success', 'Review kelengkapan tersimpan sebagai catatan audit yang tidak dapat diubah.');
    }
}
