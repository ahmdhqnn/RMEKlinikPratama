<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Services\MedicalRecordRevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MedicalRecordRevisionController extends Controller
{
    public function store(
        Patient $patient,
        MedicalRecord $medicalRecord,
        Request $request,
        MedicalRecordRevisionService $revisions,
    ): RedirectResponse {
        abort_unless($medicalRecord->patient_id === $patient->id, 404);
        Gate::authorize('revise', $medicalRecord);

        $validated = $request->validate([
            'revision_type' => ['required', Rule::in(['correction', 'addendum'])],
            'reason' => ['required', 'string', 'max:2000'],
            'subjective' => ['required_if:revision_type,correction', 'nullable', 'string', 'max:10000'],
            'objective' => ['required_if:revision_type,correction', 'nullable', 'string', 'max:10000'],
            'assessment' => ['required_if:revision_type,correction', 'nullable', 'string', 'max:10000'],
            'plan' => ['required_if:revision_type,correction', 'nullable', 'string', 'max:10000'],
            'icd10_primary_code' => ['required_if:revision_type,correction', 'nullable', 'string', 'max:20', 'exists:icd10_codes,code'],
            'tindakan_medis' => ['nullable', 'string', 'max:4000'],
            'addendum_text' => ['required_if:revision_type,addendum', 'nullable', 'string', 'max:10000'],
        ]);

        $revisions->revise($medicalRecord, $request->user(), $validated);

        return redirect()->route('pasien.show', $patient)->with('success', 'Koreksi/addendum tersimpan sebagai versi baru dan jejak sebelumnya tetap tersedia.');
    }
}
