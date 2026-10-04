<?php

namespace App\Services;

use App\Repositories\Contracts\ReportingRepositoryInterface;

class ManagementService
{
    public function __construct(
        private ReportingRepositoryInterface $reports,
        private DashboardService $dashboardService,
    ) {}

    /** @return array<string, mixed> */
    public function summary(): array
    {
        return array_merge($this->reports->managementSummary(), [
            'stats' => $this->dashboardService->stats(),
        ]);
    }
}
