<?php

namespace App\Repositories\Eloquent;

use App\Models\Patient;
use App\Models\User;
use App\Repositories\Contracts\PatientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentPatientRepository implements PatientRepositoryInterface
{
    public function search(string $term, User $viewer, int $perPage = 15): LengthAwarePaginator
    {
        return Patient::query()
            ->select([
                'id',
                'no_rm',
                'nip',
                'nama_lengkap',
                'identity_status',
                'jenis_kelamin',
                'tanggal_lahir',
                'unit_kerja',
                'jenis_pasien',
                'avatar_url',
                'created_at',
            ])
            ->withCount([
                'queues' => fn (Builder $query) => $this->scopeVisibleQueues($query, $viewer),
                'medicalRecords' => fn (Builder $query) => $query->where('doctor_id', $viewer->id),
                'attachments' => fn (Builder $query) => $query->where('uploaded_by_user_id', $viewer->id),
            ])
            ->when($viewer->hasRole('dokter', 'dokter_gigi'), fn (Builder $query) => $query->whereHas(
                'queues',
                fn (Builder $queueQuery) => $this->scopeVisibleQueues($queueQuery, $viewer),
            ))
            ->when($viewer->hasRole('perawat'), fn (Builder $query) => $query->whereHas('queues', function (Builder $queueQuery): void {
                $queueQuery->whereDate('tanggal_antrean', today())
                    ->where('status', 'menunggu_triage');
            }))
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('nama_lengkap', 'ILIKE', "%{$term}%")
                        ->orWhere('no_rm', 'ILIKE', "%{$term}%")
                        ->orWhere('nip', 'ILIKE', "%{$term}%")
                        ->orWhere('unit_kerja', 'ILIKE', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByNip(string $nip): ?Patient
    {
        return Patient::query()->where('nip', $nip)->first();
    }

    public function findByNikHash(string $nikHash): ?Patient
    {
        return Patient::query()->where('nik_hash', $nikHash)->first();
    }

    public function create(array $attributes): Patient
    {
        return Patient::query()->create($attributes);
    }

    public function findWithHistory(Patient $patient, User $viewer): Patient
    {
        $visibleQueues = fn (Builder $query) => $this->scopeVisibleQueues($query, $viewer);

        return $patient->load([
            'queues' => fn (HasMany $query) => $this->scopeVisibleQueues($query, $viewer)->with('doctor'),
            'vitalSigns' => fn (HasMany $query) => $query->whereHas('queue', $visibleQueues)->with('nurse'),
            'medicalRecords' => fn (HasMany $query) => $query->where('status', 'final')->where('doctor_id', $viewer->id)->with([
                'doctor',
                'queue',
                'prescription.items.medicine',
                'revisions',
                'downtimeReconciliations',
            ]),
            'attachments' => fn (HasMany $query) => $query->where(function (Builder $attachmentQuery) use ($viewer): void {
                $attachmentQuery->where('uploaded_by_user_id', $viewer->id)
                    ->orWhereHas('medicalRecord', fn (Builder $recordQuery) => $recordQuery->where('doctor_id', $viewer->id));
            })->with('uploader'),
            'letters' => fn (HasMany $query) => $query->where('doctor_id', $viewer->id)->with('doctor'),
            'odontograms' => fn (HasMany $query) => $query->when(
                $viewer->hasRole('dokter_gigi'),
                fn (Builder $odontogramQuery) => $odontogramQuery->where('dentist_id', $viewer->id),
                fn (Builder $odontogramQuery) => $odontogramQuery->whereRaw('1 = 0'),
            )->with('dentist'),
        ]);
    }

    private function scopeVisibleQueues(Builder|HasMany $query, User $viewer): Builder|HasMany
    {
        if ($viewer->hasRole('dokter', 'dokter_gigi')) {
            return $query
                ->where('dokter_id', $viewer->id)
                ->where('poli', $viewer->role === 'dokter_gigi' ? 'gigi' : 'umum');
        }

        if ($viewer->hasRole('perawat')) {
            return $query
                ->whereDate('tanggal_antrean', today())
                ->where('status', 'menunggu_triage');
        }

        return $query;
    }

    public function lockForIdentityUpdate(int $patientId): Patient
    {
        return Patient::query()->whereKey($patientId)->lockForUpdate()->firstOrFail();
    }
}
