<?php

namespace App\Repositories\Contracts;

use App\Models\IntegrationConfig;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Support\Collection;

interface AdministrationRepositoryInterface
{
    /** @return array<string, mixed> */
    public function overview(): array;

    /** @param array<string, mixed> $attributes */
    public function createUser(array $attributes): User;

    /** @param array<string, mixed> $attributes */
    public function createMedicine(array $attributes): Medicine;

    /** @param array<string, mixed> $attributes */
    public function createDiagnosis(array $attributes): mixed;

    /** @param array<string, mixed> $attributes */
    public function createIntegration(array $attributes): IntegrationConfig;

    /** @param array<string, mixed> $attributes */
    public function updateIntegration(IntegrationConfig $config, array $attributes): void;

    /** @param array<string, mixed> $attributes */
    public function updateUser(User $user, array $attributes): void;

    public function activeAdministratorCount(): int;

    public function lockActiveAdministrators(): Collection;
}
