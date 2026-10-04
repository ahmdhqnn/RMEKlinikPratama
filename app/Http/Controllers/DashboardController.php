<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Services\AuditLogService;
use App\Services\DashboardService;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService, QueueService $queueService, AuditLogService $auditLogService): View
    {
        $filters = $request->validate([
            'poli' => ['nullable', 'in:all,umum,gigi,selesai'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $currentUser = $request->user();
        Gate::authorize('viewDashboard', Queue::class);
        $filterPoli = $filters['poli'] ?? 'all';
        $search = trim($filters['q'] ?? '');
        $stats = $currentUser->hasRole('pendaftaran') ? $dashboardService->stats() : [];
        $queues = $queueService->todayForDashboard($filterPoli, $search, $currentUser);
        $doctors = $currentUser->hasRole('pendaftaran') ? $dashboardService->doctors() : collect();
        $auditLogService->record('VIEW_DASHBOARD', 'Membuka dashboard dan antrean.', $currentUser);

        return view('clinic.dashboard', compact('currentUser', 'stats', 'queues', 'doctors', 'filterPoli', 'search'));
    }

    public function exportReport(Request $request, QueueService $queueService, AuditLogService $auditLogService): Response
    {
        $reportQueues = $queueService->queuesForDailyReport();
        $auditLogService->record('EXPORT_REPORT', 'Mengekspor laporan kunjungan harian.', $request->user());
        $filename = 'laporan-kunjungan-'.today()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($reportQueues): void {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Poli', 'Status Kunjungan', 'Jumlah Kunjungan']);

            foreach ($reportQueues as $summary) {
                fputcsv($file, [strtoupper($summary->poli), $summary->status, $summary->jumlah_kunjungan]);
            }

            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
