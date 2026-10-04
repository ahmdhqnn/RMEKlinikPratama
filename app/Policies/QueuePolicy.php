<?php

namespace App\Policies;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\User;

class QueuePolicy
{
    public function viewDashboard(User $user): bool
    {
        return $user->hasRole('pendaftaran', 'perawat', 'dokter', 'dokter_gigi');
    }

    public function viewAny(User $user): bool
    {
        return $this->viewDashboard($user);
    }

    public function viewTriageBoard(User $user): bool
    {
        return $user->hasRole('perawat');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('pendaftaran');
    }

    public function view(User $user, Queue $queue): bool
    {
        if ($user->hasRole('pendaftaran')) {
            return $queue->tanggal_antrean?->isToday() ?? false;
        }

        if ($user->hasRole('perawat')) {
            return $queue->tanggal_antrean?->isToday()
                && $queue->status === QueueStatus::WaitingForTriage->value;
        }

        return $this->isAssignedClinician($user, $queue)
            && in_array($queue->status, [QueueStatus::UnderExamination->value, QueueStatus::ReadyForExamination->value], true);
    }

    public function call(User $user, Queue $queue): bool
    {
        return $queue->tanggal_antrean?->isToday()
            && $queue->status === QueueStatus::ReadyForExamination->value
            && $this->isAssignedClinician($user, $queue);
    }

    public function triage(User $user, Queue $queue): bool
    {
        return $user->hasRole('perawat')
            && $queue->tanggal_antrean?->isToday()
            && $queue->status === QueueStatus::WaitingForTriage->value;
    }

    public function examine(User $user, Queue $queue): bool
    {
        return $queue->status === QueueStatus::UnderExamination->value
            && $this->isAssignedClinician($user, $queue);
    }

    public function recordOdontogram(User $user, Queue $queue): bool
    {
        return $queue->poli === 'gigi'
            && $queue->status === QueueStatus::UnderExamination->value
            && $user->hasRole('dokter_gigi')
            && ($queue->dokter_id === null || $queue->dokter_id === $user->id);
    }

    public function printTicket(User $user, Queue $queue): bool
    {
        return $user->hasRole('pendaftaran');
    }

    private function isAssignedClinician(User $user, Queue $queue): bool
    {
        $expectedRole = $queue->poli === 'gigi' ? 'dokter_gigi' : 'dokter';

        return $user->hasRole($expectedRole)
            && ($queue->dokter_id === null || $queue->dokter_id === $user->id);
    }
}
