<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\AuditLogService;
use App\Services\MedicineInventoryService;
use App\Services\PharmacyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PharmacyController extends Controller
{
    public function index(Request $request, PharmacyService $pharmacyService, AuditLogService $auditLogService): View
    {
        Gate::authorize('viewAny', Prescription::class);
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $currentUser = $request->user();
        $search = trim($validated['q'] ?? '');
        $data = $pharmacyService->board($search);
        $auditLogService->record('VIEW_PHARMACY', 'Membuka antrean farmasi.', $currentUser);

        return view('clinic.farmasi', array_merge($data, compact('currentUser', 'search')));
    }

    public function verify(Request $request, Prescription $prescription, PharmacyService $pharmacyService): RedirectResponse
    {
        Gate::authorize('verify', $prescription);
        $validated = $request->validate(['catatan_apoteker' => ['nullable', 'string', 'max:2000']]);
        $pharmacyService->verify($prescription, $request->user(), $validated['catatan_apoteker'] ?? null);

        return back()->with('success', 'Resep diverifikasi dan masuk tahap penyiapan.');
    }

    public function markReady(Request $request, Prescription $prescription, PharmacyService $pharmacyService): RedirectResponse
    {
        Gate::authorize('markReady', $prescription);
        $pharmacyService->markReady($prescription, $request->user());

        return back()->with('success', 'Resep siap diserahkan kepada pasien.');
    }

    public function dispense(Request $request, Prescription $prescription, PharmacyService $pharmacyService): RedirectResponse
    {
        Gate::authorize('dispense', $prescription);
        $pharmacyService->dispense($prescription, $request->user());

        return back()->with('success', 'Obat diserahkan dan stok diperbarui.');
    }

    public function receiveStock(Request $request, Medicine $medicine, MedicineInventoryService $inventory): RedirectResponse
    {
        Gate::authorize('receiveStock', $medicine);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'nomor_batch' => ['nullable', 'string', 'max:100'],
            'expired_date' => ['nullable', 'date'],
        ]);

        $inventory->receive(
            $medicine,
            $request->user(),
            $validated['quantity'],
            $validated['reason'],
            $validated['reference'] ?? null,
            $validated['nomor_batch'] ?? null,
            $validated['expired_date'] ?? null,
        );

        return back()->with('success', "Penerimaan stok {$medicine->nama_obat} berhasil dicatat.");
    }

    public function printEtiket(
        Prescription $prescription,
        ?PrescriptionItem $item,
        Request $request,
        PharmacyService $pharmacyService,
        AuditLogService $auditLogService,
    ): View {
        Gate::authorize('printLabel', $prescription);
        [$prescription, $items] = $pharmacyService->prescriptionForPrint($prescription, $item);
        $auditLogService->record('PRINT_PRESCRIPTION_LABEL', 'Mencetak etiket resep.', $request->user(), $prescription->patient_id, [
            'prescription_id' => $prescription->id,
            'item_id' => $item?->id,
        ]);

        return view('clinic.print.etiket', compact('prescription', 'items'));
    }
}
