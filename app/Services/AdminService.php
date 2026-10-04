<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\IntegrationConfig;
use App\Models\User;
use App\Repositories\Contracts\AdministrationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminService
{
    public function __construct(
        private AdministrationRepositoryInterface $administration,
        private MedicineInventoryService $inventory,
        private AuditLogService $auditLogService,
    ) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        return $this->administration->overview();
    }

    /** @param array<string, mixed> $data */
    public function createUser(array $data, User $actor): User
    {
        $user = $this->administration->createUser(array_merge($data, [
            'password' => $data['password'],
            'is_active' => true,
        ]));
        $this->auditLogService->record('CREATE_USER', 'Akun pengguna baru dibuat.', $actor, payload: [
            'created_user_id' => $user->id,
            'role' => $user->role,
        ]);

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function createMedicine(array $data, User $actor): void
    {
        DB::transaction(function () use ($data, $actor): void {
            $medicine = $this->administration->createMedicine($data);
            $this->inventory->recordOpeningStock($medicine, $actor);
            $this->auditLogService->record('CREATE_MEDICINE', 'Master obat ditambahkan.', $actor, payload: ['medicine_id' => $medicine->id]);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function createDiagnosis(array $data, User $actor): void
    {
        $diagnosis = $this->administration->createDiagnosis($data);
        $this->auditLogService->record('CREATE_ICD10', 'Diagnosis ICD-10 ditambahkan ke master klinik.', $actor, payload: ['diagnosis_id' => $diagnosis->id]);
    }

    /** @param array<string, mixed> $data */
    public function updateIntegration(IntegrationConfig $config, array $data, User $actor): void
    {
        if (blank($data['client_secret'] ?? null)) {
            unset($data['client_secret']);
        }

        $credentialsConfigured = filled($data['client_id'] ?? $config->client_id)
            && filled($data['client_secret'] ?? $config->client_secret)
            && filled($data['organization_id'] ?? $config->organization_id)
            && filled($data['base_url'] ?? $config->base_url)
            && filled($data['auth_url'] ?? $config->auth_url);

        $this->administration->updateIntegration($config, array_merge($data, [
            'sync_status' => $credentialsConfigured ? 'configured' : 'not_configured',
        ]));
        $this->auditLogService->record('UPDATE_INTEGRATION', 'Konfigurasi integrasi diperbarui; koneksi belum diaktifkan.', $actor, payload: [
            'integration_id' => $config->id,
            'credentials_configured' => $credentialsConfigured,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function createIntegration(array $data, User $actor): void
    {
        $config = $this->administration->createIntegration(array_merge($data, [
            'is_active' => false,
            'last_synced_at' => null,
            'sync_status' => 'not_configured',
        ]));
        $this->auditLogService->record('CREATE_INTEGRATION_CONFIG', 'Konfigurasi integrasi dibuat dalam keadaan nonaktif.', $actor, payload: [
            'integration_id' => $config->id,
            'system_code' => $config->system_code,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function updateUser(User $target, array $data, User $actor): void
    {
        DB::transaction(function () use ($target, $data, $actor): void {
            $activeAdministrators = $this->administration->lockActiveAdministrators();
            $user = User::query()->whereKey($target->id)->lockForUpdate()->firstOrFail();
            $willLoseAdministratorAccess = $user->is_active
                && $user->role === UserRole::Administrator->value
                && (($data['role'] ?? $user->role) !== UserRole::Administrator->value || ! ($data['is_active'] ?? $user->is_active));

            if ($actor->id === $user->id && ! ($data['is_active'] ?? $user->is_active)) {
                throw ValidationException::withMessages(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
            }

            if ($actor->id === $user->id && ($data['role'] ?? $user->role) !== UserRole::Administrator->value) {
                throw ValidationException::withMessages(['role' => 'Anda tidak dapat mengubah peran administrator yang sedang digunakan.']);
            }

            if ($willLoseAdministratorAccess && $activeAdministrators->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Setidaknya satu administrator aktif harus dipertahankan.']);
            }

            $this->administration->updateUser($user, $data);
            $this->auditLogService->record('UPDATE_USER', 'Hak akses atau status akun diperbarui.', $actor, payload: [
                'updated_user_id' => $user->id,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]);
        }, attempts: 3);
    }
}
