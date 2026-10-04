<?php

namespace App\Repositories\Eloquent;

use App\Models\Icd10Code;
use App\Models\MedicalAttachment;
use App\Models\MedicalLetter;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Odontogram;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use App\Models\VitalSign;
use App\Repositories\Contracts\ClinicalRecordRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentClinicalRecordRepository implements ClinicalRecordRepositoryInterface
{
    public function saveVitalSign(Queue $queue, User $nurse, array $attributes): VitalSign
    {
        return VitalSign::query()->updateOrCreate(
            ['queue_id' => $queue->id],
            array_merge($attributes, [
                'patient_id' => $queue->patient_id,
                'nurse_id' => $nurse->id,
            ]),
        );
    }

    public function saveMedicalRecord(Queue $queue, User $doctor, array $attributes): MedicalRecord
    {
        return MedicalRecord::query()->updateOrCreate(
            ['queue_id' => $queue->id],
            array_merge($attributes, [
                'patient_id' => $queue->patient_id,
                'doctor_id' => $doctor->id,
            ]),
        );
    }

    public function findMedicalRecordForQueue(Queue $queue): ?MedicalRecord
    {
        return MedicalRecord::query()->where('queue_id', $queue->id)->first();
    }

    public function saveOdontogram(Queue $queue, User $dentist, array $attributes): Odontogram
    {
        return Odontogram::query()->updateOrCreate(
            ['patient_id' => $queue->patient_id],
            array_merge($attributes, [
                'medical_record_id' => $this->findMedicalRecordForQueue($queue)?->id,
                'dentist_id' => $dentist->id,
            ]),
        );
    }

    public function createAttachment(Patient $patient, User $uploader, array $attributes): MedicalAttachment
    {
        return MedicalAttachment::query()->create(array_merge($attributes, [
            'patient_id' => $patient->id,
            'uploaded_by_user_id' => $uploader->id,
        ]));
    }

    public function commonDiagnoses(): Collection
    {
        return Icd10Code::query()->where('is_common', true)->orderBy('code')->get();
    }

    public function availableMedicines(): Collection
    {
        return Medicine::query()
            ->where('is_active', true)
            ->where('stok', '>', 0)
            ->whereDate('expired_date', '>=', today())
            ->orderBy('nama_obat')
            ->orderBy('expired_date')
            ->get();
    }

    public function latestOdontogram(Patient $patient): ?Odontogram
    {
        return Odontogram::query()->where('patient_id', $patient->id)->latest('id')->first();
    }

    public function createMedicalLetter(array $attributes): MedicalLetter
    {
        return MedicalLetter::query()->create($attributes);
    }

    public function findMedicalLetter(MedicalLetter $letter): MedicalLetter
    {
        return $letter->load(['patient', 'doctor', 'medicalRecord']);
    }

    public function diagnosisByCode(string $code): ?Icd10Code
    {
        return Icd10Code::query()->where('code', $code)->first();
    }

    public function medicinesByIds(array $ids): Collection
    {
        return Medicine::query()->whereIn('id', $ids)->get()->keyBy('id');
    }

    public function searchDiagnoses(string $term): Collection
    {
        return Icd10Code::query()
            ->where(fn ($query) => $query->where('code', 'ILIKE', "%{$term}%")
                ->orWhere('name_id', 'ILIKE', "%{$term}%")
                ->orWhere('name_en', 'ILIKE', "%{$term}%"))
            ->orderBy('code')
            ->limit(15)
            ->get(['id', 'code', 'name_id', 'name_en']);
    }

    public function searchMedicines(string $term): Collection
    {
        return Medicine::query()
            ->where('is_active', true)
            ->where('stok', '>', 0)
            ->whereDate('expired_date', '>=', today())
            ->where(fn ($query) => $query->where('nama_obat', 'ILIKE', "%{$term}%")
                ->orWhere('kode_obat', 'ILIKE', "%{$term}%")
                ->orWhere('kategori', 'ILIKE', "%{$term}%"))
            ->orderBy('nama_obat')
            ->limit(15)
            ->get(['id', 'kode_obat', 'nama_obat', 'kategori', 'satuan', 'stok', 'nomor_batch', 'expired_date']);
    }
}
