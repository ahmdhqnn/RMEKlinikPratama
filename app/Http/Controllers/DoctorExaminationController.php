<?php

namespace App\Http\Controllers;

use App\Models\MedicalLetter;
use App\Models\Patient;
use App\Models\Queue;
use App\Services\AuditLogService;
use App\Services\DoctorExaminationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DoctorExaminationController extends Controller
{
    public function index(Request $request, DoctorExaminationService $examinations, AuditLogService $auditLogService, ?Queue $queue = null): View
    {
        $currentUser = $request->user();
        if ($queue) {
            Gate::authorize('view', $queue);
        }

        $room = $examinations->examinationRoom($queue, $currentUser);
        $queue = $room['queue'];

        if ($queue) {
            $auditLogService->record('VIEW_EXAMINATION_ROOM', 'Membuka ruang pemeriksaan.', $currentUser, $queue->patient_id);
        }

        return view('clinic.periksa', array_merge($room, compact('currentUser')));
    }

    public function saveSoap(Request $request, Queue $queue, DoctorExaminationService $examinations): RedirectResponse
    {
        Gate::authorize('examine', $queue);
        $validated = $request->validate($this->soapRules());
        $examinations->saveSoap($queue, $request->user(), $validated);

        return back()->with('success', 'Catatan SOAP tersimpan sebagai draf.');
    }

    public function finishSession(Request $request, Queue $queue, DoctorExaminationService $examinations): RedirectResponse
    {
        Gate::authorize('examine', $queue);
        $validated = $request->validate(array_merge($this->soapRules(), [
            'icd10_primary_code' => ['required', 'string', 'max:20', 'exists:icd10_codes,code'],
            'medicines' => ['nullable', 'array', 'max:20'],
            'medicines.*.medicine_id' => ['required', 'integer', 'distinct', 'exists:medicines,id'],
            'medicines.*.jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'medicines.*.aturan_pakai' => ['required', 'string', 'max:255'],
            'medicines.*.catatan_khusus' => ['nullable', 'string', 'max:1000'],
            'catatan_resep' => ['nullable', 'string', 'max:2000'],
            'disposition' => ['required', 'in:home,follow_up,referral'],
            'follow_up_at' => ['required_if:disposition,follow_up', 'nullable', 'date', 'after_or_equal:today'],
            'follow_up_instructions' => ['required_if:disposition,follow_up', 'nullable', 'string', 'max:2000'],
            'referral_facility' => ['required_if:disposition,referral', 'nullable', 'string', 'max:255'],
            'referral_department' => ['required_if:disposition,referral', 'nullable', 'string', 'max:255'],
            'referral_reason' => ['required_if:disposition,referral', 'nullable', 'string', 'max:2000'],
            'terbitkan_surat_sakit' => ['nullable', 'boolean'],
            'lama_hari_sakit' => ['required_if:terbitkan_surat_sakit,1', 'nullable', 'integer', 'min:1', 'max:14'],
            'tgl_mulai_sakit' => ['required_if:terbitkan_surat_sakit,1', 'nullable', 'date', 'after_or_equal:today'],
            'teeth_data_json' => ['nullable', 'json', 'max:50000'],
            'oklusi' => ['nullable', 'string', 'max:100'],
            'torus_palatinus' => ['nullable', 'string', 'max:100'],
            'torus_mandibularis' => ['nullable', 'string', 'max:100'],
            'palatum' => ['nullable', 'string', 'max:100'],
            'diastema' => ['nullable', 'string', 'max:100'],
            'anomali_gigi' => ['nullable', 'string', 'max:500'],
            'catatan_khusus' => ['nullable', 'string', 'max:2000'],
        ]));

        $examinations->finish($queue, $request->user(), $validated);

        return redirect()->route('dashboard')->with('success', "Pemeriksaan antrean {$queue->nomor_antrean} berhasil diselesaikan.");
    }

    public function uploadAttachment(Request $request, Patient $patient, DoctorExaminationService $examinations): RedirectResponse
    {
        Gate::authorize('uploadAttachment', $patient);
        $validated = $request->validate([
            'judul_dokumen' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:lab_luar,rontgen_radiologi,jawaban_rujukan,resume_medis_luar'],
            'instansi_penerbit' => ['nullable', 'string', 'max:255'],
            'tanggal_dokumen' => ['nullable', 'date', 'before_or_equal:today'],
            'catatan_interpretasi' => ['nullable', 'string', 'max:4000'],
            'berkas' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $examinations->uploadAttachment($patient, $request->user(), $validated);

        return back()->with('success', 'Berkas medis berhasil disimpan secara privat.');
    }

    public function saveOdontogram(Request $request, Queue $queue, DoctorExaminationService $examinations): RedirectResponse
    {
        Gate::authorize('recordOdontogram', $queue);
        $validated = $request->validate([
            'teeth_data_json' => ['required', 'json', 'max:50000'],
            'oklusi' => ['nullable', 'string', 'max:100'],
            'torus_palatinus' => ['nullable', 'string', 'max:100'],
            'torus_mandibularis' => ['nullable', 'string', 'max:100'],
            'palatum' => ['nullable', 'string', 'max:100'],
            'diastema' => ['nullable', 'string', 'max:100'],
            'anomali_gigi' => ['nullable', 'string', 'max:500'],
            'catatan_khusus' => ['nullable', 'string', 'max:2000'],
        ]);

        $teeth = json_decode($validated['teeth_data_json'], true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($teeth) || $teeth === []) {
            throw ValidationException::withMessages(['teeth_data_json' => 'Data odontogram tidak valid.']);
        }

        validator(['teeth' => $teeth], [
            'teeth' => ['required', 'array'],
            'teeth.*.condition' => ['required', 'in:normal,caries,amalgam,composite,missing,crown,radix,bridge'],
            'teeth.*.surfaces' => ['nullable', 'array'],
            'teeth.*.notes' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $validated['teeth_data_json'] = $teeth;
        $examinations->saveOdontogram($queue, $request->user(), $validated);

        return back()->with('success', 'Odontogram tersimpan.');
    }

    public function printLetter(
        MedicalLetter $letter,
        Request $request,
        DoctorExaminationService $examinations,
        AuditLogService $auditLogService,
    ): View {
        Gate::authorize('print', $letter);
        $letter = $examinations->printLetter($letter);
        $auditLogService->record('PRINT_MEDICAL_DRAFT', 'Mencetak draf surat internal klinik.', $request->user(), $letter->patient_id, [
            'letter_id' => $letter->id,
        ]);

        return view('clinic.print.letter', compact('letter'));
    }

    /** @return array<string, array<int, string>> */
    private function soapRules(): array
    {
        return [
            'subjective' => ['required', 'string', 'max:10000'],
            'objective' => ['required', 'string', 'max:10000'],
            'assessment' => ['required', 'string', 'max:10000'],
            'plan' => ['required', 'string', 'max:10000'],
            'icd10_primary_code' => ['nullable', 'string', 'max:20'],
            'tindakan_medis' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
