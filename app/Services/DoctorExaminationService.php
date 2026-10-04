<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\MedicalAttachment;
use App\Models\MedicalLetter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Queue;
use App\Models\User;
use App\Repositories\Contracts\ClinicalRecordRepositoryInterface;
use App\Repositories\Contracts\PharmacyRepositoryInterface;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DoctorExaminationService
{
    public function __construct(
        private QueueRepositoryInterface $queues,
        private ClinicalRecordRepositoryInterface $records,
        private PharmacyRepositoryInterface $pharmacy,
        private AuditLogService $auditLogService,
        private QueueLifecycleService $lifecycle,
        private MedicalRecordRevisionService $revisions,
    ) {}

    /** @return array<string, mixed> */
    public function examinationRoom(?Queue $queue, User $doctor): array
    {
        if (! $queue?->exists) {
            $queue = $this->queues->nextForDoctor($doctor);
        }

        if ($queue) {
            $this->assertDoctorAccess($queue, $doctor);

            $queue = $this->queues->findQueueWithRelations($queue, $doctor);
        }

        $odontogram = $queue ? $this->records->latestOdontogram($queue->patient) : null;

        return [
            'queue' => $queue,
            'commonIcd10' => $this->records->commonDiagnoses(),
            'medicines' => $this->records->availableMedicines(),
            'waitingQueues' => $this->queues->todayForDoctor($doctor),
            'odontogram' => $odontogram,
        ];
    }

    /** @param array<string, mixed> $data */
    public function saveSoap(Queue $queue, User $doctor, array $data): void
    {
        DB::transaction(function () use ($queue, $doctor, $data): void {
            $lockedQueue = $this->queues->lock($queue);
            $this->assertDoctorAccess($lockedQueue, $doctor);
            $this->assertUnderExamination($lockedQueue);

            $diagnosisCode = $data['icd10_primary_code'] ?? null;
            $diagnosisName = $data['icd10_primary_name'] ?? null;

            if ($diagnosisCode !== null) {
                $diagnosis = $this->records->diagnosisByCode($diagnosisCode);

                if (! $diagnosis) {
                    throw ValidationException::withMessages(['icd10_primary_code' => 'Kode diagnosis tidak tersedia pada master ICD-10.']);
                }

                $diagnosisName = $diagnosis->name_id;
            }

            $record = $this->records->saveMedicalRecord($lockedQueue, $doctor, [
                'subjective' => $data['subjective'],
                'objective' => $data['objective'],
                'assessment' => $data['assessment'],
                'plan' => $data['plan'],
                'icd10_primary_code' => $diagnosisCode,
                'icd10_primary_name' => $diagnosisName,
                'tindakan_medis' => $data['tindakan_medis'] ?? null,
                'status' => 'draft',
                'satusehat_sync_status' => 'not_configured',
                'satusehat_encounter_id' => null,
            ]);

            $this->auditLogService->record('SAVE_SOAP', 'Catatan SOAP disimpan sebagai draf.', $doctor, $lockedQueue->patient_id, ['medical_record_id' => $record->id]);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function finish(Queue $queue, User $doctor, array $data): ?Prescription
    {
        return DB::transaction(function () use ($queue, $doctor, $data): ?Prescription {
            $lockedQueue = $this->queues->lock($queue);
            $this->assertDoctorAccess($lockedQueue, $doctor);
            $this->assertUnderExamination($lockedQueue);

            $diagnosisCode = $data['icd10_primary_code'] ?? null;
            $diagnosisName = $data['icd10_primary_name'] ?? null;

            if (blank($diagnosisCode)) {
                throw ValidationException::withMessages(['icd10_primary_code' => 'Diagnosis ICD-10 wajib dipilih sebelum pemeriksaan difinalkan.']);
            }

            if ($diagnosisCode !== null) {
                $diagnosis = $this->records->diagnosisByCode($diagnosisCode);

                if (! $diagnosis) {
                    throw ValidationException::withMessages(['icd10_primary_code' => 'Kode diagnosis tidak tersedia pada master ICD-10.']);
                }

                $diagnosisName = $diagnosis->name_id;
            }

            $medicalRecord = $this->records->saveMedicalRecord($lockedQueue, $doctor, [
                'subjective' => $data['subjective'],
                'objective' => $data['objective'],
                'assessment' => $data['assessment'],
                'plan' => $data['plan'],
                'icd10_primary_code' => $diagnosisCode,
                'icd10_primary_name' => $diagnosisName,
                'tindakan_medis' => $data['tindakan_medis'] ?? null,
                'status' => 'final',
                'signed_by_user_id' => $doctor->id,
                'signed_by_name' => $doctor->name,
                'signed_by_role' => $doctor->role,
                'signed_at' => now(),
                'disposition' => $data['disposition'],
                'follow_up_at' => $data['disposition'] === 'follow_up' ? $data['follow_up_at'] : null,
                'follow_up_instructions' => $data['disposition'] === 'follow_up' ? $data['follow_up_instructions'] : null,
                'satusehat_sync_status' => 'not_configured',
                'satusehat_encounter_id' => null,
            ]);

            $this->revisions->recordOriginal($medicalRecord, $doctor);

            $prescription = null;
            $items = $data['medicines'] ?? [];

            if ($items !== []) {
                $medicineIds = collect($items)->pluck('medicine_id')->unique()->sort()->all();
                $medicines = $this->records->medicinesByIds($medicineIds);

                foreach ($items as $item) {
                    $medicine = $medicines->get($item['medicine_id']);

                    if (! $medicine || ! $medicine->is_active || ! $medicine->expired_date || $medicine->expired_date->lt(today())) {
                        throw ValidationException::withMessages(['medicines' => 'Obat yang dipilih tidak aktif atau sudah kedaluwarsa.']);
                    }
                }

                $prescription = $this->pharmacy->createPrescription([
                    'nomor_resep' => 'RX-'.now()->format('Ym').'-'.str_pad((string) $lockedQueue->id, 6, '0', STR_PAD_LEFT),
                    'queue_id' => $lockedQueue->id,
                    'patient_id' => $lockedQueue->patient_id,
                    'doctor_id' => $doctor->id,
                    'status' => 'pending',
                    'catatan_dokter_farmasi' => $data['catatan_resep'] ?? null,
                ]);

                $this->pharmacy->createItems($prescription, array_map(static fn (array $item): array => [
                    'medicine_id' => $item['medicine_id'],
                    'jumlah' => $item['jumlah'],
                    'aturan_pakai' => $item['aturan_pakai'],
                    'catatan_khusus' => $item['catatan_khusus'] ?? null,
                ], $items));
            }

            $teeth = json_decode($data['teeth_data_json'] ?? '{}', true);
            if ($lockedQueue->poli === 'gigi' && is_array($teeth) && $teeth !== []) {
                $teeth = $this->validatedTeethData($teeth);
                $this->records->saveOdontogram($lockedQueue, $doctor, [
                    'teeth_data_json' => $teeth,
                    'oklusi' => $data['oklusi'] ?? null,
                    'torus_palatinus' => $data['torus_palatinus'] ?? null,
                    'torus_mandibularis' => $data['torus_mandibularis'] ?? null,
                    'palatum' => $data['palatum'] ?? null,
                    'diastema' => $data['diastema'] ?? null,
                    'anomali_gigi' => $data['anomali_gigi'] ?? null,
                    'catatan_khusus' => $data['catatan_khusus'] ?? null,
                ]);
            }

            $this->lifecycle->transition(
                $lockedQueue,
                $prescription ? QueueStatus::WaitingForPharmacy : QueueStatus::Completed,
                $doctor,
                $prescription
                    ? 'Pemeriksaan selesai dan resep diteruskan ke farmasi.'
                    : 'Pemeriksaan selesai tanpa resep.',
                $prescription ? [] : ['waktu_selesai' => now()],
            );

            if ($data['terbitkan_surat_sakit'] ?? false) {
                $this->issueSickLeave($lockedQueue->patient_id, $medicalRecord->id, $doctor, $medicalRecord->icd10_primary_name, $data);
            }

            if ($data['disposition'] === 'referral') {
                $this->records->createMedicalLetter([
                    'patient_id' => $lockedQueue->patient_id,
                    'medical_record_id' => $medicalRecord->id,
                    'doctor_id' => $doctor->id,
                    'jenis_surat' => 'surat_rujukan',
                    'nomor_surat' => 'RJK/'.$lockedQueue->tanggal_antrean->format('Ymd').'/'.Str::upper(Str::random(8)),
                    'tanggal_surat' => today(),
                    'tujuan_faskes_rujukan' => $data['referral_facility'],
                    'poli_tujuan_rujukan' => $data['referral_department'],
                    'alasan_rujukan' => $data['referral_reason'],
                    'icd10_code' => $medicalRecord->icd10_primary_code,
                    'catatan_tambahan' => $medicalRecord->assessment,
                    'qr_token' => Str::random(48),
                ]);
            }

            $this->auditLogService->record(
                $prescription ? 'SUBMIT_PRESCRIPTION' : 'FINISH_CONSULTATION',
                $prescription ? "Resep {$prescription->nomor_resep} diteruskan ke farmasi." : 'Pemeriksaan selesai tanpa resep.',
                $doctor,
                $lockedQueue->patient_id,
                ['medical_record_id' => $medicalRecord->id, 'prescription_id' => $prescription?->id, 'disposition' => $data['disposition']],
            );

            return $prescription;
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function saveOdontogram(Queue $queue, User $dentist, array $data): void
    {
        $data['teeth_data_json'] = $this->validatedTeethData($data['teeth_data_json']);

        DB::transaction(function () use ($queue, $dentist, $data): void {
            $lockedQueue = $this->queues->lock($queue);
            abort_unless($lockedQueue->poli === 'gigi', 403);
            $this->assertDoctorAccess($lockedQueue, $dentist);
            $this->assertUnderExamination($lockedQueue);
            $this->records->saveOdontogram($lockedQueue, $dentist, $data);
            $this->auditLogService->record('SAVE_ODONTOGRAM', 'Odontogram pasien diperbarui.', $dentist, $lockedQueue->patient_id);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function uploadAttachment(Patient $patient, User $uploader, array $data): void
    {
        abort_unless($uploader->hasRole('dokter', 'dokter_gigi'), 403);

        $file = $data['berkas'];
        $path = $file->store('medical-attachments', 'local');

        try {
            $attachment = $this->records->createAttachment($patient, $uploader, [
                'kategori' => $data['kategori'],
                'judul_dokumen' => $data['judul_dokumen'],
                'instansi_penerbit' => $data['instansi_penerbit'] ?? null,
                'tanggal_dokumen' => $data['tanggal_dokumen'] ?? today(),
                'file_path' => $path,
                'file_type' => $file->extension(),
                'file_size_kb' => (int) ceil($file->getSize() / 1024),
                'catatan_interpretasi' => $data['catatan_interpretasi'] ?? null,
            ]);

            $this->auditLogService->record('UPLOAD_ATTACHMENT', 'Berkas medis eksternal disimpan.', $uploader, $patient->id, ['attachment_id' => $attachment->id]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }

    public function printLetter(MedicalLetter $letter): MedicalLetter
    {
        abort_unless($letter->jenis_surat === 'surat_sakit', 404);

        return $this->records->findMedicalLetter($letter);
    }

    public function downloadAttachment(Patient $patient, MedicalAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->patient_id === $patient->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, Str::slug($attachment->judul_dokumen).'.'.$attachment->file_type);
    }

    private function assertUnderExamination(Queue $queue): void
    {
        if ($queue->status !== QueueStatus::UnderExamination->value) {
            throw ValidationException::withMessages(['queue' => 'Perubahan klinis hanya dapat dilakukan pada antrean yang sedang diperiksa.']);
        }
    }

    private function assertDoctorAccess(Queue $queue, User $doctor): void
    {
        $expectedRole = $queue->poli === 'gigi' ? 'dokter_gigi' : 'dokter';
        abort_unless($doctor->hasRole($expectedRole), 403);
        abort_if($queue->dokter_id && $queue->dokter_id !== $doctor->id, 403);
    }

    /** @param array<int|string, mixed> $teeth
     * @return array<int|string, mixed>
     */
    private function validatedTeethData(array $teeth): array
    {
        $allowedTeeth = [11, 12, 13, 14, 15, 16, 17, 18, 21, 22, 23, 24, 25, 26, 27, 28,
            31, 32, 33, 34, 35, 36, 37, 38, 41, 42, 43, 44, 45, 46, 47, 48,
            51, 52, 53, 54, 55, 61, 62, 63, 64, 65, 71, 72, 73, 74, 75, 81, 82, 83, 84, 85];

        if ($teeth === [] || count($teeth) > count($allowedTeeth) || array_diff(array_map('intval', array_keys($teeth)), $allowedTeeth) !== []) {
            throw ValidationException::withMessages(['teeth_data_json' => 'Data gigi tidak valid atau kosong.']);
        }

        validator(['teeth' => $teeth], [
            'teeth' => ['required', 'array', 'max:52'],
            'teeth.*.condition' => ['required', 'in:normal,caries,amalgam,composite,missing,crown,radix,bridge'],
            'teeth.*.surfaces' => ['nullable', 'array'],
            'teeth.*.surfaces.*' => ['string', 'max:30'],
            'teeth.*.notes' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return $teeth;
    }

    /** @param array<string, mixed> $data */
    private function issueSickLeave(int $patientId, int $recordId, User $doctor, ?string $diagnosis, array $data): void
    {
        $startDate = Carbon::parse($data['tgl_mulai_sakit']);
        $days = (int) $data['lama_hari_sakit'];
        $endDate = $startDate->copy()->addDays($days - 1);
        $number = 'SKS/'.$startDate->format('Ymd').'/'.Str::upper(Str::random(8));

        $this->records->createMedicalLetter([
            'patient_id' => $patientId,
            'medical_record_id' => $recordId,
            'doctor_id' => $doctor->id,
            'jenis_surat' => 'surat_sakit',
            'nomor_surat' => $number,
            'tanggal_surat' => today(),
            'lama_hari' => $days,
            'tanggal_mulai' => $startDate->toDateString(),
            'tanggal_selesai' => $endDate->toDateString(),
            'keperluan' => 'Istirahat medis',
            'status_kesehatan' => 'Istirahat sesuai penilaian dokter',
            'catatan_tambahan' => $diagnosis,
            'qr_token' => Str::random(48),
        ]);
    }
}
