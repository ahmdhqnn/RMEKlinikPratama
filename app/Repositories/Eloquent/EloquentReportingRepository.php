<?php

namespace App\Repositories\Eloquent;

use App\Enums\QueueStatus;
use App\Models\IntegrationConfig;
use App\Models\MedicalRecord;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\Queue;
use App\Models\User;
use App\Repositories\Contracts\ReportingRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class EloquentReportingRepository implements ReportingRepositoryInterface
{
    public function dashboardStats(): array
    {
        $today = today();
        $queues = Queue::query()->whereDate('tanggal_antrean', $today);
        $total = (clone $queues)->where('status', '!=', QueueStatus::Cancelled->value)->count();
        $completed = (clone $queues)->where('status', QueueStatus::Completed->value)->count();
        $generalTotal = (clone $queues)->where('poli', 'umum')->where('status', '!=', QueueStatus::Cancelled->value)->count();
        $generalCompleted = (clone $queues)->where('poli', 'umum')->where('status', QueueStatus::Completed->value)->count();
        $dentalTotal = (clone $queues)->where('poli', 'gigi')->where('status', '!=', QueueStatus::Cancelled->value)->count();
        $dentalCompleted = (clone $queues)->where('poli', 'gigi')->where('status', QueueStatus::Completed->value)->count();
        $prescriptions = Prescription::query()->whereDate('created_at', $today);
        $prescriptionTotal = (clone $prescriptions)->count();
        $prescriptionCompleted = (clone $prescriptions)->where('status', 'dispensed')->count();

        return [
            'total_pasien' => $total,
            'selesai_pasien' => $completed,
            'pending_pasien' => max(0, $total - $completed),
            'poli_umum_total' => $generalTotal,
            'poli_umum_selesai' => $generalCompleted,
            'poli_umum_tunggu' => max(0, $generalTotal - $generalCompleted),
            'poli_gigi_total' => $dentalTotal,
            'poli_gigi_selesai' => $dentalCompleted,
            'poli_gigi_tunggu' => max(0, $dentalTotal - $dentalCompleted),
            'resep_total' => $prescriptionTotal,
            'resep_selesai' => $prescriptionCompleted,
            'resep_antre' => max(0, $prescriptionTotal - $prescriptionCompleted),
        ];
    }

    public function managementSummary(): array
    {
        $topDiseases = MedicalRecord::query()
            ->select('icd10_primary_code', 'icd10_primary_name')
            ->selectRaw('COUNT(*) AS total')
            ->where('status', 'final')
            ->whereNotNull('icd10_primary_code')
            ->whereHas('queue', fn ($query) => $query->whereBetween('tanggal_antrean', [today()->startOfMonth(), today()]))
            ->groupBy('icd10_primary_code', 'icd10_primary_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topMedicines = Medicine::query()
            ->leftJoin('prescription_items', 'medicines.id', '=', 'prescription_items.medicine_id')
            ->leftJoin('prescriptions', function ($join): void {
                $join->on('prescription_items.prescription_id', '=', 'prescriptions.id')
                    ->where('prescriptions.status', '=', 'dispensed')
                    ->whereDate('prescriptions.waktu_penyerahan', '>=', today()->startOfMonth())
                    ->whereDate('prescriptions.waktu_penyerahan', '<=', today());
            })
            ->select('medicines.id', 'medicines.nama_obat', 'medicines.stok', 'medicines.satuan', 'medicines.kategori', 'medicines.bentuk_sediaan')
            ->selectRaw('COALESCE(SUM(prescription_items.jumlah), 0) AS jumlah_diserahkan')
            ->groupBy('medicines.id', 'medicines.nama_obat', 'medicines.stok', 'medicines.satuan', 'medicines.kategori', 'medicines.bentuk_sediaan')
            ->orderByDesc('jumlah_diserahkan')
            ->limit(8)
            ->get();

        $start = CarbonImmutable::today()->startOfMonth()->subMonths(5);
        $monthlyRows = Queue::query()
            ->selectRaw("TO_CHAR(tanggal_antrean, 'YYYY-MM') AS month_key, COUNT(*) AS total")
            ->whereBetween('tanggal_antrean', [$start->toDateString(), today()->toDateString()])
            ->where('status', '!=', QueueStatus::Cancelled->value)
            ->groupBy('month_key')
            ->get()
            ->keyBy('month_key');

        $monthlyVisits = [];

        for ($month = $start; $month <= CarbonImmutable::today()->startOfMonth(); $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $monthlyVisits[$month->translatedFormat('F Y')] = (int) ($monthlyRows->get($key)?->total ?? 0);
        }

        $monthVisits = Queue::query()
            ->whereBetween('tanggal_antrean', [today()->startOfMonth(), today()])
            ->where('status', '!=', QueueStatus::Cancelled->value)
            ->count();
        $averageWait = Queue::query()
            ->whereBetween('tanggal_antrean', [today()->startOfMonth(), today()])
            ->whereNotNull('waktu_panggil')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (waktu_panggil - created_at)) / 60) AS average_minutes')
            ->value('average_minutes');
        $finalRecords = MedicalRecord::query()->where('status', 'final')
            ->whereHas('queue', fn ($query) => $query->whereBetween('tanggal_antrean', [today()->startOfMonth(), today()]));
        $examinedQueues = Queue::query()
            ->whereBetween('tanggal_antrean', [today()->startOfMonth(), today()])
            ->whereNotNull('waktu_panggil')
            ->count();
        $integrationsConfigured = IntegrationConfig::query()->where('sync_status', 'configured')->count();
        $metrics = [
            'monthVisits' => $monthVisits,
            'averageWaitMinutes' => $averageWait === null ? null : round((float) $averageWait, 1),
            'recordCompletionPercent' => $examinedQueues === 0 ? null : round($finalRecords->count() / $examinedQueues * 100, 1),
            'integrationsConfigured' => $integrationsConfigured,
        ];

        return compact('topDiseases', 'topMedicines', 'monthlyVisits', 'metrics');
    }

    public function activeDoctors(): Collection
    {
        return User::query()
            ->whereIn('role', ['dokter', 'dokter_gigi'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'specialty', 'sip', 'avatar_url']);
    }
}
