<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use App\Repositories\Contracts\PatientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientService
{
    public function __construct(
        private PatientRepositoryInterface $patients,
        private AuditLogService $auditLogService,
    ) {}

    public function search(string $term, User $viewer, int $perPage = 15): LengthAwarePaginator
    {
        return $this->patients->search($term, $viewer, $perPage);
    }

    public function findWithHistory(Patient $patient, User $viewer): Patient
    {
        return $this->patients->findWithHistory($patient, $viewer);
    }

    /** @param array<string, mixed> $attributes */
    public function register(array $attributes): Patient
    {
        $identityIsUnknown = (bool) ($attributes['identitas_tidak_diketahui'] ?? false);
        $nikHash = ! $identityIsUnknown && filled($attributes['nik'] ?? null)
            ? hash_hmac('sha256', preg_replace('/\D+/', '', $attributes['nik']), config('app.key'))
            : null;
        $nip = ! $identityIsUnknown && filled($attributes['nip'] ?? null) ? $attributes['nip'] : null;
        $patientByNip = $nip ? $this->patients->findByNip($nip) : null;
        $patientByNik = $nikHash ? $this->patients->findByNikHash($nikHash) : null;

        if ($patientByNip && $patientByNik && $patientByNip->id !== $patientByNik->id) {
            throw ValidationException::withMessages([
                'nik' => 'NIK dan NIP mengarah ke data pasien yang berbeda. Periksa kembali identitas pasien.',
            ]);
        }

        $patient = $patientByNip ?? $patientByNik;

        if ($patient) {
            $identityName = preg_replace('/\s+/u', ' ', mb_strtolower(trim($attributes['nama_lengkap'] ?? '')));
            $registeredName = preg_replace('/\s+/u', ' ', mb_strtolower(trim($patient->nama_lengkap)));
            $identityDate = $attributes['tanggal_lahir'] ?? null;

            if (
                $identityName !== $registeredName
                || ($attributes['jenis_kelamin'] !== null && $patient->jenis_kelamin !== $attributes['jenis_kelamin'])
                || ($identityDate && $patient->tanggal_lahir && ! $patient->tanggal_lahir->isSameDay($identityDate))
                || ($nip && $patient->nip && $patient->nip !== $nip)
                || ($nikHash && $patient->nik_hash && ! hash_equals($patient->nik_hash, $nikHash))
            ) {
                throw ValidationException::withMessages([
                    'nik' => 'Identitas cocok dengan rekam medis yang ada tetapi data pokoknya berbeda. Hentikan pendaftaran dan minta verifikasi rekam medis.',
                ]);
            }

            return $patient;
        }

        $medicalRecordNumber = 'RM-'.now()->format('Y').'-'.Str::upper(Str::random(8));

        return $this->patients->create([
            'no_rm' => $medicalRecordNumber,
            'nik' => $identityIsUnknown ? null : ($attributes['nik'] ?? null),
            'nik_hash' => $nikHash,
            'nip' => $nip,
            'nama_lengkap' => filled($attributes['nama_lengkap'] ?? null) ? trim($attributes['nama_lengkap']) : 'Belum Teridentifikasi',
            'identity_status' => $identityIsUnknown ? 'unidentified' : 'provisional',
            'identity_verified_at' => null,
            'jenis_kelamin' => $attributes['jenis_kelamin'] ?? null,
            'tempat_lahir' => $attributes['tempat_lahir'] ?? null,
            'tanggal_lahir' => $attributes['tanggal_lahir'] ?? null,
            'golongan_darah' => $attributes['golongan_darah'] ?? null,
            'no_bpjs' => $attributes['no_bpjs'] ?? null,
            'jenis_pasien' => $attributes['jenis_pasien'] ?? 'Umum',
            'unit_kerja' => $attributes['unit_kerja'] ?? null,
            'no_hp' => $attributes['no_hp'] ?? null,
            'alamat' => $attributes['alamat'] ?? null,
            'riwayat_alergi' => $attributes['riwayat_alergi'] ?? null,
            'alergi_keterangan' => $attributes['alergi_keterangan'] ?? null,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    public function reconcileIdentity(Patient $patient, array $attributes, User $actor): Patient
    {
        return DB::transaction(function () use ($patient, $attributes, $actor): Patient {
            $lockedPatient = $this->patients->lockForIdentityUpdate($patient->id);
            $nik = filled($attributes['nik'] ?? null) ? preg_replace('/\D+/', '', $attributes['nik']) : null;
            $nikHash = $nik !== null ? hash_hmac('sha256', $nik, config('app.key')) : null;
            $nip = filled($attributes['nip'] ?? null) ? trim($attributes['nip']) : null;

            if ($nikHash !== null) {
                $patientWithNik = $this->patients->findByNikHash($nikHash);

                if ($patientWithNik !== null && $patientWithNik->id !== $lockedPatient->id) {
                    throw ValidationException::withMessages([
                        'nik' => 'NIK sudah terhubung ke nomor rekam medis lain. Hentikan perubahan dan minta telaah petugas berwenang.',
                    ]);
                }
            }

            if ($nip !== null) {
                $patientWithNip = $this->patients->findByNip($nip);

                if ($patientWithNip !== null && $patientWithNip->id !== $lockedPatient->id) {
                    throw ValidationException::withMessages([
                        'nip' => 'NIP sudah terhubung ke nomor rekam medis lain. Hentikan perubahan dan minta telaah petugas berwenang.',
                    ]);
                }
            }

            $verificationRequested = (bool) ($attributes['identity_verified'] ?? false);
            $identityDataChanged = trim($attributes['nama_lengkap']) !== $lockedPatient->nama_lengkap
                || ($attributes['tanggal_lahir'] ?? null) !== $lockedPatient->tanggal_lahir?->format('Y-m-d')
                || ($attributes['jenis_kelamin'] ?? null) !== $lockedPatient->jenis_kelamin
                || ($nikHash !== null && $nikHash !== $lockedPatient->nik_hash)
                || ($nip !== null && $nip !== $lockedPatient->nip);
            $retainExistingVerification = $lockedPatient->identity_status === 'verified'
                && ! $verificationRequested
                && ! $identityDataChanged;
            $verified = $verificationRequested || $retainExistingVerification;
            $verificationMethod = $verificationRequested
                ? $attributes['identity_verification_method']
                : ($retainExistingVerification ? $lockedPatient->identity_verification_method : null);
            $previousIdentityStatus = $lockedPatient->identity_status;
            $previousVerificationMethod = $lockedPatient->identity_verification_method;
            $changes = [
                'nama_lengkap' => trim($attributes['nama_lengkap']),
                'tanggal_lahir' => $attributes['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $attributes['jenis_kelamin'] ?? null,
                'identity_status' => $verified ? 'verified' : 'provisional',
                'identity_verified_at' => $verificationRequested
                    ? now()
                    : ($retainExistingVerification ? $lockedPatient->identity_verified_at : null),
                'identity_verification_method' => $verificationMethod,
            ];

            if ($nik !== null) {
                $changes['nik'] = $nik;
                $changes['nik_hash'] = $nikHash;
            }

            if ($nip !== null) {
                $changes['nip'] = $nip;
            }

            $changedFields = [];

            foreach ($changes as $field => $value) {
                if ($lockedPatient->getAttribute($field) != $value) {
                    $changedFields[] = $field;
                }
            }

            $lockedPatient->fill($changes)->save();

            $this->auditLogService->record(
                'RECONCILE_PATIENT_IDENTITY',
                'Petugas pendaftaran memperbarui identitas pasien.',
                $actor,
                $lockedPatient->id,
                [
                    'changed_fields' => $changedFields,
                    'identity_status_before' => $previousIdentityStatus,
                    'identity_status' => $lockedPatient->identity_status,
                    'verification_method_before' => $previousVerificationMethod,
                    'verification_method' => $lockedPatient->identity_verification_method,
                ],
            );

            return $lockedPatient->refresh();
        }, attempts: 3);
    }
}
