<?php

namespace App\Services;

use App\Repositories\Contracts\ReportingRepositoryInterface;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private ReportingRepositoryInterface $reports) {}

    /** @return array<string, int> */
    public function stats(): array
    {
        return $this->reports->dashboardStats();
    }

    public function doctors(): Collection
    {
        return $this->reports->activeDoctors();
    }
}
