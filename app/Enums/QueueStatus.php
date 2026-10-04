<?php

namespace App\Enums;

enum QueueStatus: string
{
    case WaitingForTriage = 'menunggu_triage';
    case ReadyForExamination = 'siap_periksa';
    case UnderExamination = 'sedang_diperiksa';
    case WaitingForPharmacy = 'menunggu_obat';
    case BeingDispensed = 'sedang_dispensing';
    case ReadyForPickup = 'siap_diambil';
    case Completed = 'selesai';
    case Cancelled = 'dibatalkan';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::WaitingForTriage => [self::ReadyForExamination],
            self::ReadyForExamination => [self::UnderExamination],
            self::UnderExamination => [self::WaitingForPharmacy, self::Completed],
            self::WaitingForPharmacy => [self::BeingDispensed],
            self::BeingDispensed => [self::ReadyForPickup],
            self::ReadyForPickup => [self::Completed],
            self::Completed, self::Cancelled => [],
        }, true);
    }
}
