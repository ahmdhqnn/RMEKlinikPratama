<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Queue;
use App\Repositories\Contracts\ClinicalRecordRepositoryInterface;
use App\Repositories\Contracts\QueueRepositoryInterface;
use App\Services\DashboardService;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApiController extends Controller
{
    public function searchPatients(Request $request, PatientService $patients)
    {
        Gate::authorize('viewAny', Patient::class);
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        if (mb_strlen($validated['q']) < 2) {
            return response()->json([]);
        }

        return response()->json($patients->search($validated['q'], $request->user(), 10)->getCollection()->map(fn ($patient) => [
            'id' => $patient->id,
            'no_rm' => $patient->no_rm,
            'nama_lengkap' => $patient->nama_lengkap,
            'nip' => $patient->nip,
            'jenis_kelamin' => $patient->jenis_kelamin,
            'identity_status' => $patient->identity_status,
        ]));
    }

    public function searchIcd10(Request $request, ClinicalRecordRepositoryInterface $records)
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        return response()->json($records->searchDiagnoses($validated['q']));
    }

    public function searchMedicines(Request $request, ClinicalRecordRepositoryInterface $records)
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        return response()->json($records->searchMedicines($validated['q']));
    }

    public function getQueueUpdates(Request $request, QueueRepositoryInterface $queues, DashboardService $dashboard)
    {
        Gate::authorize('viewDashboard', Queue::class);
        $currentUser = $request->user();
        $board = $queues->todayForLiveBoard($currentUser)->map(fn ($queue) => [
            'id' => $queue->id,
            'nomor_antrean' => $queue->nomor_antrean,
            'poli' => $queue->poli,
            'status' => $queue->status,
            'prioritas' => $queue->prioritas,
            'is_emergency' => $queue->is_emergency,
            'pasien' => $queue->patient?->nama_lengkap,
            'dokter' => $queue->doctor?->name,
        ]);

        return response()->json([
            'stats' => $currentUser->hasRole('pendaftaran') ? $dashboard->stats() : [],
            'queues' => $board,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
