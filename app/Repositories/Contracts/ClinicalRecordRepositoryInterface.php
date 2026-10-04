<?php

namespace App\Repositories\Contracts;

use App\Models\Icd10Code;
use App\Models\MedicalAttachment;
use App\Models\MedicalLetter;
use App\Models\MedicalRecord;
use App\Models\Odontogram;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Support\Collection;

interface ClinicalRecordRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function saveVitalSign(Queue $queue, User $nurse, array $attributes): VitalSign;

    /** @param array<string, mixed> $attributes */
    public function saveMedicalRecord(Queue $queue, User $doctor, array $attributes): MedicalRecord;

    public function findMedicalRecordForQueue(Queue $queue): ?MedicalRecord;

    /** @param array<string, mixed> $attributes */
    public function saveOdontogram(Queue $queue, User $dentist, array $attributes): Odontogram;

    /** @param array<string, mixed> $attributes */
    public function createAttachment(Patient $patient, User $uploader, array $attributes): MedicalAttachment;

    public function commonDiagnoses(): Collection;

    public function availableMedicines(): Collection;

    public function latestOdontogram(Patient $patient): ?Odontogram;

    /** @param array<string, mixed> $attributes */
    public function createMedicalLetter(array $attributes): MedicalLetter;

    public function findMedicalLetter(MedicalLetter $letter): MedicalLetter;

    public function diagnosisByCode(string $code): ?Icd10Code;

    /** @param list<int> $ids */
    public function medicinesByIds(array $ids): Collection;

    public function searchDiagnoses(string $term): Collection;

    public function searchMedicines(string $term): Collection;
}
