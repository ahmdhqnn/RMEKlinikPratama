<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Repositories\Contracts\PharmacyRepositoryInterface;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyService
{
    public function __construct(
        private PharmacyRepositoryInterface $pharmacy,
        private QueueRepositoryInterface $queues,
        private MedicineInventoryService $inventory,
        private AuditLogService $auditLogService,
        private QueueLifecycleService $lifecycle,
    ) {}

    /** @return array<string, mixed> */
    public function board(string $search): array
    {
        return $this->pharmacy->board($search);
    }

    public function verify(Prescription $prescription, User $pharmacist, ?string $note): void
    {
        abort_unless($pharmacist->hasRole('farmasi'), 403);

        DB::transaction(function () use ($prescription, $pharmacist, $note): void {
            $lockedPrescription = $this->pharmacy->lockPrescription($prescription);

            if ($lockedPrescription->status !== 'pending') {
                throw ValidationException::withMessages(['prescription' => 'Hanya resep yang baru masuk yang dapat diverifikasi.']);
            }

            $queue = $this->queues->lock($lockedPrescription->queue);
            if ($queue->status !== QueueStatus::WaitingForPharmacy->value) {
                throw ValidationException::withMessages(['prescription' => 'Status antrean tidak sesuai dengan resep masuk.']);
            }

            if ($lockedPrescription->items()->count() === 0) {
                throw ValidationException::withMessages(['prescription' => 'Resep tanpa item obat tidak dapat diproses.']);
            }

            $this->assertItemsAvailable($lockedPrescription);

            $lockedPrescription->update([
                'status' => 'processing',
                'pharmacist_id' => $pharmacist->id,
                'waktu_verifikasi' => now(),
                'catatan_apoteker' => $note,
            ]);
            $this->lifecycle->transition(
                $queue,
                QueueStatus::BeingDispensed,
                $pharmacist,
                'Farmasi memulai verifikasi dan penyiapan resep.',
            );
            $this->auditLogService->record('VERIFY_PRESCRIPTION', "Resep {$lockedPrescription->nomor_resep} diverifikasi.", $pharmacist, $lockedPrescription->patient_id);
        }, attempts: 3);
    }

    public function markReady(Prescription $prescription, User $pharmacist): void
    {
        abort_unless($pharmacist->hasRole('farmasi'), 403);

        DB::transaction(function () use ($prescription, $pharmacist): void {
            $lockedPrescription = $this->pharmacy->lockPrescription($prescription);

            if ($lockedPrescription->status !== 'processing') {
                throw ValidationException::withMessages(['prescription' => 'Resep harus diverifikasi sebelum ditandai siap.']);
            }

            $queue = $this->queues->lock($lockedPrescription->queue);
            if ($queue->status !== QueueStatus::BeingDispensed->value) {
                throw ValidationException::withMessages(['prescription' => 'Status antrean tidak sesuai dengan proses penyiapan resep.']);
            }

            $this->assertItemsAvailable($lockedPrescription);

            $lockedPrescription->update(['status' => 'ready', 'pharmacist_id' => $pharmacist->id]);
            $this->lifecycle->transition(
                $queue,
                QueueStatus::ReadyForPickup,
                $pharmacist,
                'Obat selesai disiapkan dan siap diserahkan.',
            );
            $this->auditLogService->record('READY_PRESCRIPTION', "Resep {$lockedPrescription->nomor_resep} siap diserahkan.", $pharmacist, $lockedPrescription->patient_id);
        }, attempts: 3);
    }

    public function dispense(Prescription $prescription, User $pharmacist): void
    {
        abort_unless($pharmacist->hasRole('farmasi'), 403);

        DB::transaction(function () use ($prescription, $pharmacist): void {
            $lockedPrescription = $this->pharmacy->lockPrescription($prescription);

            if ($lockedPrescription->status !== 'ready') {
                throw ValidationException::withMessages(['prescription' => 'Hanya obat yang sudah siap yang dapat diserahkan.']);
            }

            $queue = $this->queues->lock($lockedPrescription->queue);
            if ($queue->status !== QueueStatus::ReadyForPickup->value) {
                throw ValidationException::withMessages(['prescription' => 'Status antrean tidak sesuai dengan resep siap diserahkan.']);
            }

            $items = $lockedPrescription->items()->orderBy('medicine_id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['prescription' => 'Resep tidak memiliki item obat untuk diserahkan.']);
            }

            foreach ($items as $item) {
                $medicine = $this->pharmacy->lockMedicine($item->medicine_id);

                if (! $medicine->is_active || ! $medicine->expired_date || $medicine->expired_date->lt(today())) {
                    throw ValidationException::withMessages([
                        'prescription' => "Obat {$medicine->nama_obat} tidak aktif atau telah kedaluwarsa.",
                    ]);
                }

                if ($medicine->stok < $item->jumlah) {
                    throw ValidationException::withMessages([
                        'prescription' => "Stok {$medicine->nama_obat} tidak mencukupi untuk resep ini.",
                    ]);
                }

                $this->inventory->recordDispensing($medicine, $pharmacist, $item->jumlah, $lockedPrescription->nomor_resep);
                $item->update(['is_dispensed' => true]);
            }

            $lockedPrescription->update([
                'status' => 'dispensed',
                'waktu_penyerahan' => now(),
                'pharmacist_id' => $pharmacist->id,
            ]);
            $this->lifecycle->transition(
                $queue,
                QueueStatus::Completed,
                $pharmacist,
                'Obat diserahkan; proses pelayanan selesai.',
                ['waktu_selesai' => now()],
            );
            $this->auditLogService->record('DISPENSE_PRESCRIPTION', "Resep {$lockedPrescription->nomor_resep} diserahkan.", $pharmacist, $lockedPrescription->patient_id);
        }, attempts: 3);
    }

    public function prescriptionForPrint(Prescription $prescription, ?PrescriptionItem $item): array
    {
        $prescription = $this->pharmacy->findPrescription($prescription->id);

        if ($item?->exists && $item->prescription_id !== $prescription->id) {
            abort(404);
        }

        return [$prescription, $item?->exists ? collect([$item->load('medicine')]) : $prescription->items];
    }

    private function assertItemsAvailable(Prescription $prescription): void
    {
        $items = $prescription->items()->orderBy('medicine_id')->get();

        foreach ($items as $item) {
            $medicine = $this->pharmacy->lockMedicine($item->medicine_id);

            if (! $medicine->is_active || ! $medicine->expired_date || $medicine->expired_date->lt(today())) {
                throw ValidationException::withMessages(['prescription' => "Obat {$medicine->nama_obat} tidak aktif atau telah kedaluwarsa."]);
            }

            if ($medicine->stok < $item->jumlah) {
                throw ValidationException::withMessages(['prescription' => "Stok {$medicine->nama_obat} tidak mencukupi untuk resep ini."]);
            }
        }
    }
}
