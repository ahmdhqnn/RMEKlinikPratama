<?php

namespace App\Enums;

enum MedicalRecordQualityReviewFollowUpStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Belum dikerjakan',
            self::InProgress => 'Sedang dikerjakan',
            self::Resolved => 'Selesai setelah koreksi',
            self::NotApplicable => 'Tidak perlu koreksi',
        };
    }

    public function isResolved(): bool
    {
        return $this === self::Resolved || $this === self::NotApplicable;
    }
}
