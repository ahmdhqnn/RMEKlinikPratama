<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface ReportingRepositoryInterface
{
    /** @return array<string, int> */
    public function dashboardStats(): array;

    /** @return array{topDiseases: Collection, topMedicines: Collection, monthlyVisits: array<string, int>, metrics: array<string, int|float|null>} */
    public function managementSummary(): array;

    public function activeDoctors(): Collection;
}
