<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'admin';
    case Registration = 'pendaftaran';
    case Nurse = 'perawat';
    case Doctor = 'dokter';
    case Dentist = 'dokter_gigi';
    case Pharmacy = 'farmasi';
    case ClinicManager = 'kepala_klinik';
    case MedicalRecordsOfficer = 'perekam_medis';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Registration => 'Pendaftaran',
            self::Nurse => 'Perawat',
            self::Doctor => 'Dokter Umum',
            self::Dentist => 'Dokter Gigi',
            self::Pharmacy => 'Farmasi',
            self::ClinicManager => 'Kepala Klinik',
            self::MedicalRecordsOfficer => 'Perekam Medis (PMIK)',
        };
    }
}
