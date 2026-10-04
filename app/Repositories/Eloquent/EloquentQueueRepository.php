<?php

namespace App\Repositories\Eloquent;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\QueueStatusHistory;
use App\Models\User;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentQueueRepository implements QueueRepositoryInterface
{
    public function nextNumber(CarbonInterface $date, string $poli): string
    {
        DB::table('queue_sequences')->insertOrIgnore([
            'tanggal_antrean' => $date->toDateString(),
            'poli' => $poli,
            'last_number' => 0,
        ]);

        $sequence = DB::table('queue_sequences')
            ->where('tanggal_antrean', $date->toDateString())
            ->where('poli', $poli)
            ->lockForUpdate()
            ->first();

        $number = $sequence->last_number + 1;

        DB::table('queue_sequences')->where('id', $sequence->id)->update(['last_number' => $number]);

        return ($poli === 'umum' ? 'A' : 'G').'-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function create(array $attributes): Queue
    {
        return Queue::query()->create($attributes);
    }

    public function lock(Queue $queue): Queue
    {
        return Queue::query()->whereKey($queue->getKey())->lockForUpdate()->firstOrFail();
    }

    public function createStatusHistory(array $attributes): QueueStatusHistory
    {
        return QueueStatusHistory::query()->create($attributes);
    }

    public function assignedDoctor(string $poli): ?User
    {
        return User::query()
            ->where('role', $poli === 'gigi' ? 'dokter_gigi' : 'dokter')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    public function todayForDashboard(string $filterPoli, string $search, User $user): LengthAwarePaginator
    {
        $query = Queue::query()
            ->with(['patient:id,no_rm,nama_lengkap,nip,identity_status', 'doctor:id,name', 'vitalSign:id,queue_id,sistolik,diastolik,suhu'])
            ->whereDate('tanggal_antrean', today());

        if ($user->hasRole('perawat')) {
            $query->where('status', QueueStatus::WaitingForTriage->value);
        } elseif ($user->hasRole('dokter', 'dokter_gigi')) {
            $poli = $user->role === 'dokter_gigi' ? 'gigi' : 'umum';
            $query->where('poli', $poli)
                ->whereIn('status', [QueueStatus::UnderExamination->value, QueueStatus::ReadyForExamination->value])
                ->where(fn ($doctorQuery) => $doctorQuery->where('dokter_id', $user->id)->orWhereNull('dokter_id'));
        } elseif (in_array($filterPoli, ['umum', 'gigi'], true)) {
            $query->where('poli', $filterPoli)->whereNot('status', QueueStatus::Completed->value);
        } elseif ($filterPoli === 'selesai') {
            $query->where('status', QueueStatus::Completed->value);
        }

        return $query
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nomor_antrean', 'ILIKE', "%{$search}%")
                        ->orWhereHas('patient', function ($patientQuery) use ($search): void {
                            $patientQuery->where('nama_lengkap', 'ILIKE', "%{$search}%")
                                ->orWhere('no_rm', 'ILIKE', "%{$search}%")
                                ->orWhere('nip', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("CASE WHEN status = 'sedang_diperiksa' THEN 1 WHEN status = 'siap_periksa' THEN 2 WHEN status = 'menunggu_triage' THEN 3 WHEN status = 'menunggu_obat' THEN 4 WHEN status = 'sedang_dispensing' THEN 5 WHEN status = 'siap_diambil' THEN 6 ELSE 7 END")
            ->orderByRaw("CASE WHEN prioritas = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();
    }

    public function todayForLiveBoard(User $user): Collection
    {
        return Queue::query()
            ->with(['patient:id,no_rm,nama_lengkap', 'doctor:id,name'])
            ->whereDate('tanggal_antrean', today())
            ->when($user->hasRole('perawat'), fn ($query) => $query->where('status', QueueStatus::WaitingForTriage->value))
            ->when($user->hasRole('dokter', 'dokter_gigi'), function ($query) use ($user): void {
                $query->where('poli', $user->role === 'dokter_gigi' ? 'gigi' : 'umum')
                    ->whereIn('status', [QueueStatus::UnderExamination->value, QueueStatus::ReadyForExamination->value])
                    ->where(fn ($doctorQuery) => $doctorQuery->where('dokter_id', $user->id)->orWhereNull('dokter_id'));
            })
            ->orderByRaw("CASE WHEN prioritas = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get(['id', 'nomor_antrean', 'patient_id', 'dokter_id', 'poli', 'status', 'prioritas', 'is_emergency', 'waktu_daftar', 'tanggal_antrean']);
    }

    public function todayForTriage(): Collection
    {
        return Queue::query()
            ->with(['patient', 'vitalSign', 'doctor'])
            ->whereDate('tanggal_antrean', today())
            ->where('status', QueueStatus::WaitingForTriage->value)
            ->orderByRaw("CASE WHEN prioritas = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }

    public function todayForDoctor(User $doctor): Collection
    {
        return Queue::query()
            ->with(['patient', 'doctor', 'vitalSign', 'medicalRecord.prescription.items.medicine', 'medicalRecord.odontogram'])
            ->whereDate('tanggal_antrean', today())
            ->whereIn('status', [QueueStatus::UnderExamination->value, QueueStatus::ReadyForExamination->value])
            ->where('poli', $doctor->role === 'dokter_gigi' ? 'gigi' : 'umum')
            ->where(fn ($query) => $query->where('dokter_id', $doctor->id)->orWhereNull('dokter_id'))
            ->orderByRaw("CASE WHEN status = 'sedang_diperiksa' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN prioritas = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }

    public function nextForDoctor(User $doctor): ?Queue
    {
        return Queue::query()
            ->with(['patient', 'doctor', 'vitalSign', 'medicalRecord.prescription.items.medicine', 'medicalRecord.odontogram'])
            ->whereDate('tanggal_antrean', today())
            ->whereIn('status', [QueueStatus::UnderExamination->value, QueueStatus::ReadyForExamination->value])
            ->where('poli', $doctor->role === 'dokter_gigi' ? 'gigi' : 'umum')
            ->where(fn ($query) => $query->where('dokter_id', $doctor->id)->orWhereNull('dokter_id'))
            ->orderByRaw("CASE WHEN status = 'sedang_diperiksa' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN prioritas = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    public function todayForReport(): Collection
    {
        return Queue::query()
            ->whereDate('tanggal_antrean', today())
            ->selectRaw('poli, status, COUNT(*) AS jumlah_kunjungan')
            ->groupBy('poli', 'status')
            ->orderBy('poli')
            ->orderBy('status')
            ->get();
    }

    public function findQueueWithRelations(Queue $queue, ?User $viewer = null): Queue
    {
        $relations = [
            'patient',
            'doctor',
            'vitalSign',
            'medicalRecord.prescription.items.medicine',
            'medicalRecord.odontogram',
            'statusHistories',
        ];

        if ($viewer !== null) {
            $relations['patient.attachments'] = fn (HasMany $query) => $query->where(function (Builder $attachmentQuery) use ($viewer): void {
                $attachmentQuery->where('uploaded_by_user_id', $viewer->id)
                    ->orWhereHas('medicalRecord', fn (Builder $recordQuery) => $recordQuery->where('doctor_id', $viewer->id));
            });
        }

        return $queue->load($relations);
    }
}
