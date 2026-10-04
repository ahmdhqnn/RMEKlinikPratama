<?php

namespace App\Http\Controllers;

use App\Models\MedicalAttachment;
use App\Models\Patient;
use App\Services\AuditLogService;
use App\Services\DoctorExaminationService;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    private const IDENTITY_VERIFICATION_METHODS = [
        'identity_document' => 'Dokumen identitas resmi dilihat langsung',
        'employee_card' => 'Kartu pegawai atau kartu peserta magang dilihat langsung',
        'other_official_document' => 'Dokumen resmi lain dilihat langsung',
    ];

    public function index(Request $request, PatientService $patients, AuditLogService $auditLogService): View
    {
        Gate::authorize('viewAny', Patient::class);
        $currentUser = $request->user();
        $search = $request->string('q')->trim()->limit(100)->toString();
        $patientPage = $patients->search($search, $currentUser);
        $auditLogService->record('VIEW_PATIENT_LIST', 'Membuka direktori rekam medis pasien.', $currentUser);

        return view('clinic.pasien.index', ['currentUser' => $currentUser, 'patients' => $patientPage, 'search' => $search]);
    }

    public function show(Patient $patient, Request $request, PatientService $patients, AuditLogService $auditLogService): View
    {
        Gate::authorize('view', $patient);
        $currentUser = $request->user();
        $patient = $patients->findWithHistory($patient, $currentUser);
        $auditLogService->record('VIEW_PATIENT_DETAIL', 'Membuka detail rekam medis pasien.', $currentUser, $patient->id);

        return view('clinic.pasien.show', compact('currentUser', 'patient'));
    }

    public function printCard(Patient $patient, Request $request, AuditLogService $auditLogService): View
    {
        Gate::authorize('printCard', $patient);
        $auditLogService->record('PRINT_PATIENT_CARD', 'Mencetak kartu pasien.', $request->user(), $patient->id);

        return view('clinic.print.patient_card', compact('patient'));
    }

    public function editIdentity(Patient $patient, Request $request, AuditLogService $auditLogService): View
    {
        Gate::authorize('reconcileIdentity', $patient);
        $auditLogService->record('VIEW_PATIENT_IDENTITY', 'Membuka formulir identitas administratif pasien.', $request->user(), $patient->id);

        return view('clinic.pasien.identity', [
            'patientId' => $patient->id,
            'medicalRecordNumber' => $patient->no_rm,
            'patientName' => $patient->nama_lengkap,
            'identityStatus' => $patient->identity_status,
            'dateOfBirth' => $patient->tanggal_lahir,
            'sex' => $patient->jenis_kelamin,
            'verificationMethod' => $patient->identity_verification_method,
            'hasNik' => filled($patient->nik_hash),
            'hasNip' => filled($patient->nip),
            'verificationMethods' => self::IDENTITY_VERIFICATION_METHODS,
        ]);
    }

    public function updateIdentity(Patient $patient, Request $request, PatientService $patients): RedirectResponse
    {
        Gate::authorize('reconcileIdentity', $patient);

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'tanggal_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'nik' => ['nullable', 'string', 'regex:/^[0-9\\s-]{16,24}$/'],
            'nip' => ['nullable', 'string', 'max:30'],
            'identity_verified' => ['nullable', 'boolean'],
            'identity_verification_method' => [
                Rule::requiredIf($request->boolean('identity_verified')),
                'nullable',
                Rule::in(array_keys(self::IDENTITY_VERIFICATION_METHODS)),
            ],
        ]);

        if (filled($validated['nik'] ?? null) && strlen(preg_replace('/\\D+/', '', $validated['nik'])) !== 16) {
            throw ValidationException::withMessages(['nik' => 'NIK harus terdiri dari 16 angka.']);
        }

        $patients->reconcileIdentity($patient, $validated, $request->user());

        return redirect()->route('pasien.identity.edit', $patient)->with('status', 'Identitas pasien berhasil diperbarui dan dicatat pada audit trail.');
    }

    public function downloadAttachment(
        Patient $patient,
        MedicalAttachment $attachment,
        Request $request,
        DoctorExaminationService $examinations,
        AuditLogService $auditLogService,
    ): StreamedResponse {
        abort_unless($attachment->patient_id === $patient->id, 404);
        Gate::authorize('view', [MedicalAttachment::class, $patient, $attachment]);
        $auditLogService->record('DOWNLOAD_MEDICAL_ATTACHMENT', 'Mengunduh berkas medis pasien.', $request->user(), $patient->id, [
            'attachment_id' => $attachment->id,
        ]);

        return $examinations->downloadAttachment($patient, $attachment);
    }
}
