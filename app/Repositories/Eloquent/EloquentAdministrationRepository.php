<?php

namespace App\Repositories\Eloquent;

use App\Models\AuditLog;
use App\Models\Icd10Code;
use App\Models\IntegrationConfig;
use App\Models\Medicine;
use App\Models\User;
use App\Repositories\Contracts\AdministrationRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentAdministrationRepository implements AdministrationRepositoryInterface
{
    public function overview(): array
    {
        return [
            'users' => User::query()->orderBy('name')->paginate(25, ['*'], 'users_page'),
            'medicines' => Medicine::query()->orderBy('nama_obat')->paginate(25, ['*'], 'medicines_page'),
            'icdCodes' => Icd10Code::query()->orderBy('code')->paginate(25, ['*'], 'icd_page'),
            'auditLogs' => AuditLog::query()
                ->whereNull('patient_id')
                ->with('user')
                ->orderByDesc('id')
                ->paginate(25, ['*'], 'audit_page'),
            'integrations' => IntegrationConfig::query()->orderBy('system_name')->get(),
        ];
    }

    public function createUser(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function createMedicine(array $attributes): Medicine
    {
        return Medicine::query()->create($attributes);
    }

    public function createDiagnosis(array $attributes): Icd10Code
    {
        return Icd10Code::query()->create($attributes);
    }

    public function createIntegration(array $attributes): IntegrationConfig
    {
        return IntegrationConfig::query()->create($attributes);
    }

    public function updateIntegration(IntegrationConfig $config, array $attributes): void
    {
        $config->update(array_merge($attributes, [
            'is_active' => false,
            'last_synced_at' => null,
        ]));
    }

    public function updateUser(User $user, array $attributes): void
    {
        $user->update($attributes);
    }

    public function activeAdministratorCount(): int
    {
        return User::query()->where('role', 'admin')->where('is_active', true)->count();
    }

    public function lockActiveAdministrators(): Collection
    {
        return User::query()->where('role', 'admin')->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
    }
}
