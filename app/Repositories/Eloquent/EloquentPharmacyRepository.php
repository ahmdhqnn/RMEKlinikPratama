<?php

namespace App\Repositories\Eloquent;

use App\Models\Medicine;
use App\Models\Prescription;
use App\Repositories\Contracts\PharmacyRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentPharmacyRepository implements PharmacyRepositoryInterface
{
    public function board(string $search): array
    {
        $baseQuery = Prescription::query()
            ->with(['patient:id,no_rm,nama_lengkap,riwayat_alergi,alergi_keterangan', 'doctor:id,name', 'pharmacist:id,name', 'items.medicine', 'queue'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nomor_resep', 'ILIKE', "%{$search}%")
                        ->orWhereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery->where('nama_lengkap', 'ILIKE', "%{$search}%")
                                ->orWhere('no_rm', 'ILIKE', "%{$search}%");
                        });
                });
            });

        $pending = (clone $baseQuery)->where('status', 'pending')->orderBy('id')->paginate(25, ['*'], 'pending_page')->withQueryString();
        $processing = (clone $baseQuery)->where('status', 'processing')->orderBy('id')->paginate(25, ['*'], 'processing_page')->withQueryString();
        $ready = (clone $baseQuery)->where('status', 'ready')->orderBy('id')->paginate(25, ['*'], 'ready_page')->withQueryString();
        $todayQuery = (clone $baseQuery)->whereDate('created_at', today());
        $dispensedCount = (clone $todayQuery)->where('status', 'dispensed')->count();
        $latestPrescription = (clone $baseQuery)->where('status', 'pending')->with(['doctor:id,name', 'patient:id,nama_lengkap,no_rm'])->latest('id')->first();

        return [
            'pendingPrescriptions' => $pending,
            'processingPrescriptions' => $processing,
            'readyPrescriptions' => $ready,
            'dispensedPrescriptions' => collect(),
            'pendingCount' => $pending->total(),
            'processingCount' => $processing->total(),
            'readyCount' => $ready->total(),
            'dispensedCount' => $dispensedCount,
            'totalToday' => $todayQuery->count(),
            'lowStockMedicines' => Medicine::query()->where('is_active', true)->whereColumn('stok', '<=', 'stok_minimum')->orderBy('stok')->get(),
            'latestPrescription' => $latestPrescription,
        ];
    }

    public function findPrescription(int $id): Prescription
    {
        return Prescription::query()->with(['patient', 'doctor', 'items.medicine', 'queue'])->findOrFail($id);
    }

    public function lockPrescription(Prescription $prescription): Prescription
    {
        return Prescription::query()->whereKey($prescription->getKey())->lockForUpdate()->firstOrFail();
    }

    public function lockMedicine(int $id): Medicine
    {
        return Medicine::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function createPrescription(array $attributes): Prescription
    {
        return Prescription::query()->create($attributes);
    }

    public function createItems(Prescription $prescription, array $items): Collection
    {
        return $prescription->items()->createMany($items);
    }
}
