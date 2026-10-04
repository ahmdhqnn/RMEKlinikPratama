<?php

namespace App\Http\Controllers;

use App\Models\DowntimeReconciliation;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Services\AuditLogService;
use App\Services\DowntimeReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DowntimeReconciliationController extends Controller
{
    public function index(Request $request, DowntimeReconciliationService $reconciliations, AuditLogService $auditLogService): View
    {
        Gate::authorize('viewAny', DowntimeReconciliation::class);
        $validated = $request->validate([
            'filter' => ['nullable', Rule::in(['pending_review', 'discrepancy', 'reconciled', 'all'])],
        ]);
        $filter = $validated['filter'] ?? 'pending_review';
        $auditLogService->record('VIEW_DOWNTIME_RECONCILIATION_QUEUE', 'Membuka antrean rekonsiliasi downtime.', $request->user(), payload: [
            'filter' => $filter,
        ]);
        $records = $reconciliations->paginateForReview($filter);

        return view('clinic.downtime_reconciliations.index', [
            'records' => $records,
            'events' => $reconciliations->eventsForReconciliations($records->getCollection()->pluck('id')->all()),
            'filter' => $filter,
        ]);
    }

    public function store(
        Request $request,
        Patient $patient,
        MedicalRecord $medicalRecord,
        DowntimeReconciliationService $reconciliations,
    ): RedirectResponse {
        Gate::authorize('create', DowntimeReconciliation::class);
        abort_unless($medicalRecord->patient_id === $patient->id, 404);
        $validated = $request->validate([
            'paper_reference' => ['required', 'string', 'max:120', 'unique:downtime_reconciliations,paper_reference'],
            'service_occurred_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        $reconciliations->createFromDowntime(
            $medicalRecord,
            $request->user(),
            $validated['paper_reference'],
            $validated['service_occurred_at'],
        );

        return redirect()
            ->route('pasien.show', $patient)
            ->with('success', 'Metadata formulir downtime dicatat untuk review PMIK. Waktu finalisasi sistem tidak diubah.');
    }

    public function update(Request $request, DowntimeReconciliation $downtimeReconciliation, DowntimeReconciliationService $reconciliations): RedirectResponse
    {
        Gate::authorize('update', $downtimeReconciliation);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['reconciled', 'discrepancy'])],
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $reconciliations->review(
            $downtimeReconciliation,
            $request->user(),
            $validated['status'],
            $validated['note'],
        );

        return redirect()
            ->route('downtime-reconciliations.index')
            ->with('success', 'Hasil review rekonsiliasi downtime dan jejak audit tersimpan.');
    }
}
