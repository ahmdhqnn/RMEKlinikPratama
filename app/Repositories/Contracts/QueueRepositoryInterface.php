<?php

namespace App\Repositories\Contracts;

use App\Models\Queue;
use App\Models\QueueStatusHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface QueueRepositoryInterface
{
    public function nextNumber(CarbonInterface $date, string $poli): string;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Queue;

    public function lock(Queue $queue): Queue;

    /** @param array<string, mixed> $attributes */
    public function createStatusHistory(array $attributes): QueueStatusHistory;

    public function assignedDoctor(string $poli): ?User;

    public function todayForDashboard(string $filterPoli, string $search, User $user): LengthAwarePaginator;

    public function todayForTriage(): Collection;

    public function todayForDoctor(User $doctor): Collection;

    public function nextForDoctor(User $doctor): ?Queue;

    public function todayForLiveBoard(User $user): Collection;

    public function todayForReport(): Collection;

    public function findQueueWithRelations(Queue $queue, ?User $viewer = null): Queue;
}
