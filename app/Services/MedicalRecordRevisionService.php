<?php

namespace App\Services;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordRevision;
use App\Models\User;
use App\Repositories\Contracts\ClinicalRecordRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicalRecordRevisionService
{
    private const SNAPSHOT_FIELDS = [
        'subjective',
        'objective',
        'assessment',
        'plan',
        'icd10_primary_code',
        'icd10_primary_name',
        'icd10_secondary_json',
        'tindakan_medis',
        'status',
        'disposition',
        'follow_up_at',
        'follow_up_instructions',
        'doctor_id',
        'signed_by_user_id',
        'signed_by_name',
        'signed_by_role',
        'signed_at',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        private ClinicalRecordRepositoryInterface $records,
        private AuditLogService $auditLogService,
    ) {}

    public function recordOriginal(MedicalRecord $medicalRecord, User $doctor): MedicalRecordRevision
    {
        return MedicalRecordRevision::query()->create([
            'medical_record_id' => $medicalRecord->id,
            'version_number' => ((int) MedicalRecordRevision::query()
                ->where('medical_record_id', $medicalRecord->id)
                ->max('version_number')) + 1,
            'revision_type' => 'original',
            'reason' => 'Catatan klinis pertama kali difinalkan.',
            'snapshot_json' => $this->snapshot($medicalRecord),
            'recorded_by_user_id' => $doctor->id,
            'recorded_by_name' => $doctor->name,
            'recorded_by_role' => $doctor->role,
            'origin' => 'application',
            'recorded_at' => $medicalRecord->signed_at ?? now(),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function revise(MedicalRecord $medicalRecord, User $doctor, array $data): MedicalRecordRevision
    {
        return DB::transaction(function () use ($medicalRecord, $doctor, $data): MedicalRecordRevision {
            $lockedRecord = MedicalRecord::query()
                ->with('queue')
                ->whereKey($medicalRecord->id)
                ->lockForUpdate()
                ->firstOrFail();

            $expectedRole = $lockedRecord->queue?->poli === 'gigi' ? 'dokter_gigi' : 'dokter';
            abort_unless(
                $lockedRecord->status === 'final'
                    && $lockedRecord->doctor_id === $doctor->id
                    && $doctor->hasRole($expectedRole),
                403,
            );

            $revisionType = $data['revision_type'] ?? null;
            if (! is_string($revisionType) || ! in_array($revisionType, ['correction', 'addendum'], true) || blank($data['reason'] ?? null)) {
                throw ValidationException::withMessages(['revision_type' => 'Jenis revisi dan alasannya wajib dicatat.']);
            }

            $latestVersion = (int) MedicalRecordRevision::query()
                ->where('medical_record_id', $lockedRecord->id)
                ->max('version_number');

            if ($latestVersion === 0) {
                MedicalRecordRevision::query()->create([
                    'medical_record_id' => $lockedRecord->id,
                    'version_number' => 1,
                    'revision_type' => 'original',
                    'reason' => 'Snapshot awal sebelum revisi; tidak merekonstruksi perubahan historis.',
                    'snapshot_json' => $this->snapshot($lockedRecord),
                    'recorded_by_name' => 'Snapshot aktivasi versioning',
                    'recorded_by_role' => 'system',
                    'origin' => 'activation_snapshot',
                    'recorded_at' => now(),
                ]);

                $latestVersion = 1;
            }

            if ($revisionType === 'correction') {
                $diagnosisCode = $data['icd10_primary_code'] ?? null;
                $diagnosisName = null;

                if (filled($diagnosisCode)) {
                    $diagnosis = $this->records->diagnosisByCode($diagnosisCode);

                    if (! $diagnosis) {
                        throw ValidationException::withMessages([
                            'icd10_primary_code' => 'Kode diagnosis tidak tersedia pada master ICD-10.',
                        ]);
                    }

                    $diagnosisName = $diagnosis->name_id;
                }

                $lockedRecord->fill([
                    'subjective' => $data['subjective'],
                    'objective' => $data['objective'],
                    'assessment' => $data['assessment'],
                    'plan' => $data['plan'],
                    'icd10_primary_code' => $diagnosisCode,
                    'icd10_primary_name' => $diagnosisName,
                    'tindakan_medis' => $data['tindakan_medis'] ?? null,
                ]);
                $lockedRecord->save();
            }

            $revision = MedicalRecordRevision::query()->create([
                'medical_record_id' => $lockedRecord->id,
                'version_number' => $latestVersion + 1,
                'revision_type' => $revisionType,
                'reason' => $data['reason'],
                'snapshot_json' => $this->snapshot($lockedRecord),
                'addendum_text' => $revisionType === 'addendum' ? $data['addendum_text'] : null,
                'recorded_by_user_id' => $doctor->id,
                'recorded_by_name' => $doctor->name,
                'recorded_by_role' => $doctor->role,
                'origin' => 'application',
                'recorded_at' => now(),
            ]);

            $this->auditLogService->record(
                $revisionType === 'correction' ? 'CORRECT_FINAL_MEDICAL_RECORD' : 'ADD_MEDICAL_RECORD_ADDENDUM',
                $revisionType === 'correction' ? 'Catatan final dikoreksi dengan versi baru.' : 'Addendum ditambahkan tanpa mengubah catatan sebelumnya.',
                $doctor,
                $lockedRecord->patient_id,
                [
                    'medical_record_id' => $lockedRecord->id,
                    'revision_id' => $revision->id,
                    'version_number' => $revision->version_number,
                    'revision_type' => $revision->revision_type,
                    'reason' => $revision->reason,
                ],
            );

            return $revision;
        }, attempts: 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(MedicalRecord $medicalRecord): array
    {
        return collect(self::SNAPSHOT_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $medicalRecord->getAttribute($field)])
            ->all();
    }
}
