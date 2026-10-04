<?php

namespace App\Repositories\Contracts;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PatientRepositoryInterface
{
    public function search(string $term, User $viewer, int $perPage = 15): LengthAwarePaginator;

    public function findByNip(string $nip): ?Patient;

    public function findByNikHash(string $nikHash): ?Patient;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Patient;

    public function findWithHistory(Patient $patient, User $viewer): Patient;

    public function lockForIdentityUpdate(int $patientId): Patient;
}
