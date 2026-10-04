<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\User;
use App\Repositories\Contracts\MedicineInventoryRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicineInventoryService
{
    public function __construct(
        private MedicineInventoryRepositoryInterface $inventory,
        private AuditLogService $auditLogService,
    ) {}

    public function recordOpeningStock(Medicine $medicine, User $actor): void
    {
        if ($medicine->stok < 1) {
            return;
        }

        $this->inventory->createMovement([
            'medicine_id' => $medicine->id,
            'user_id' => $actor->id,
            'movement_type' => 'opening',
            'quantity' => $medicine->stok,
            'stock_before' => 0,
            'stock_after' => $medicine->stok,
            'reason' => 'Stok awal saat master obat dibuat.',
            'occurred_at' => now(),
        ]);
    }

    public function receive(
        Medicine $medicine,
        User $actor,
        int $quantity,
        string $reason,
        ?string $reference,
        ?string $batchNumber,
        ?string $expirationDate,
    ): void {
        abort_unless($actor->hasRole('farmasi'), 403);

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah penerimaan harus lebih dari nol.']);
        }

        DB::transaction(function () use ($medicine, $actor, $quantity, $reason, $reference, $batchNumber, $expirationDate): void {
            $lockedMedicine = $this->inventory->lockMedicine($medicine);

            if (! $lockedMedicine->is_active || ! $lockedMedicine->expired_date || $lockedMedicine->expired_date->lt(today())) {
                throw ValidationException::withMessages(['medicine' => 'Stok hanya dapat diterima untuk batch obat yang aktif dan belum kedaluwarsa.']);
            }

            if (trim($lockedMedicine->nomor_batch ?? '') !== trim($batchNumber ?? '')) {
                throw ValidationException::withMessages(['nomor_batch' => 'Nomor batch penerimaan harus sama dengan batch obat yang dipilih. Buat master terpisah untuk batch baru.']);
            }

            $storedExpiration = $lockedMedicine->expired_date->toDateString();

            if ($storedExpiration !== $expirationDate) {
                throw ValidationException::withMessages(['expired_date' => 'Tanggal kedaluwarsa penerimaan harus sama dengan batch obat yang dipilih. Buat master terpisah untuk batch baru.']);
            }

            if ($lockedMedicine->stok > 2147483647 - $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Stok melebihi batas penyimpanan.']);
            }

            $stockBefore = $lockedMedicine->stok;
            $stockAfter = $stockBefore + $quantity;
            $lockedMedicine->update(['stok' => $stockAfter]);
            $this->inventory->createMovement([
                'medicine_id' => $lockedMedicine->id,
                'user_id' => $actor->id,
                'movement_type' => 'receipt',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $reason,
                'reference' => $reference,
                'occurred_at' => now(),
            ]);
            $this->auditLogService->record('RECEIVE_MEDICINE_STOCK', 'Penerimaan stok obat dicatat.', $actor, payload: [
                'medicine_id' => $lockedMedicine->id,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reference' => $reference,
            ]);
        }, attempts: 3);
    }

    public function recordDispensing(Medicine $medicine, User $actor, int $quantity, ?string $reference): void
    {
        if ($quantity < 1 || $medicine->stok < $quantity) {
            throw ValidationException::withMessages(['medicine' => 'Stok obat tidak mencukupi untuk transaksi ini.']);
        }

        $stockBefore = $medicine->stok;
        $stockAfter = $stockBefore - $quantity;
        $medicine->update(['stok' => $stockAfter]);
        $this->inventory->createMovement([
            'medicine_id' => $medicine->id,
            'user_id' => $actor->id,
            'movement_type' => 'dispense',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => 'Penyerahan obat berdasarkan resep.',
            'reference' => $reference,
            'occurred_at' => now(),
        ]);
    }
}
