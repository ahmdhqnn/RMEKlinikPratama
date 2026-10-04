<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\AuditLogService;
use App\Services\TriageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TriageController extends Controller
{
    public function index(TriageService $triageService, AuditLogService $auditLogService): View
    {
        $currentUser = request()->user();
        Gate::authorize('viewTriageBoard', Queue::class);
        $queues = $triageService->waitingQueues();
        $auditLogService->record('VIEW_TRIAGE', 'Membuka antrean triase.', $currentUser);

        return view('clinic.triage', compact('currentUser', 'queues'));
    }

    public function storeTtv(Request $request, Queue $queue, TriageService $triageService): RedirectResponse
    {
        Gate::authorize('triage', $queue);
        $validated = $request->validate([
            'sistolik' => ['required', 'numeric', 'min:50', 'max:260'],
            'diastolik' => ['required', 'numeric', 'min:30', 'max:160'],
            'suhu' => ['required', 'numeric', 'min:30', 'max:45'],
            'nadi' => ['nullable', 'numeric', 'min:30', 'max:220'],
            'laju_nafas' => ['nullable', 'numeric', 'min:8', 'max:60'],
            'spo2' => ['nullable', 'numeric', 'min:50', 'max:100'],
            'berat_badan' => ['nullable', 'numeric', 'min:1', 'max:300'],
            'tinggi_badan' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'kesadaran' => ['nullable', 'string', 'max:50'],
            'skala_nyeri' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'catatan_keperawatan' => ['nullable', 'string', 'max:2000'],
        ]);

        if (collect($validated)->except(['kesadaran', 'skala_nyeri', 'catatan_keperawatan'])->filter(fn ($value): bool => $value !== null)->isEmpty()) {
            throw ValidationException::withMessages(['sistolik' => 'Isi minimal satu hasil pemeriksaan tanda vital.']);
        }

        $triageService->recordVitals($queue, $request->user(), $validated);

        return redirect()->route('dashboard')->with('success', 'Tanda vital tersimpan dan pasien siap masuk ke ruang pemeriksaan.');
    }
}
