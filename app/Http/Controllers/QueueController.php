<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\AuditLogService;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function store(Request $request, QueueService $queueService): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Queue::class);
        $validated = $request->validate([
            'identitas_tidak_diketahui' => ['required', 'boolean'],
            'nama_lengkap' => ['required_unless:identitas_tidak_diketahui,1', 'nullable', 'string', 'max:255'],
            'nip' => ['prohibited_if:identitas_tidak_diketahui,1', 'nullable', 'string', 'max:30'],
            'nik' => ['prohibited_if:identitas_tidak_diketahui,1', 'nullable', 'digits:16'],
            'jenis_kelamin' => ['required_unless:identitas_tidak_diketahui,1', 'nullable', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required_unless:identitas_tidak_diketahui,1', 'nullable', 'date', 'before_or_equal:today'],
            'golongan_darah' => ['nullable', 'string', 'max:10'],
            'unit_kerja' => ['nullable', 'string', 'max:255'],
            'jenis_pasien' => ['nullable', 'string', 'max:50'],
            'no_bpjs' => ['nullable', 'string', 'max:50'],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'riwayat_alergi' => ['nullable', 'string', 'max:2000'],
            'alergi_keterangan' => ['nullable', 'string', 'max:2000'],
            'poli' => ['required', 'in:umum,gigi'],
            'keluhan_singkat' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['identitas_tidak_diketahui'] = $request->boolean('identitas_tidak_diketahui');

        $queue = $queueService->register($validated, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Antrean berhasil didaftarkan.',
                'queue' => $queue,
            ], 201);
        }

        return redirect()->route('dashboard')->with('success', "Pasien {$queue->patient->nama_lengkap} mendapat nomor antrean {$queue->nomor_antrean}.");
    }

    public function callNext(Request $request, Queue $queue, QueueService $queueService): RedirectResponse|JsonResponse
    {
        Gate::authorize('call', $queue);
        $queue = $queueService->call($queue, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['queue' => $queue]);
        }

        return redirect()->route('periksa.show', $queue)->with('success', "Antrean {$queue->nomor_antrean} dipanggil ke ruang pemeriksaan.");
    }

    public function printTicket(Queue $queue, Request $request, QueueService $queueService, AuditLogService $auditLogService): View
    {
        Gate::authorize('printTicket', $queue);
        $queue = $queueService->queueForPrint($queue);
        $auditLogService->record('PRINT_QUEUE_TICKET', 'Mencetak tiket antrean.', $request->user(), $queue->patient_id, [
            'queue_id' => $queue->id,
        ]);

        return view('clinic.print.ticket', compact('queue'));
    }
}
