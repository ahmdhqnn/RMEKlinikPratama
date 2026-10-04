<?php

namespace Tests\Feature;

use App\Models\DowntimeReconciliation;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DowntimeReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_records_downtime_metadata_without_changing_finalization_time(): void
    {
        $medicalRecord = MedicalRecord::factory()->create(['signed_at' => now()->subHours(3)]);
        $doctor = User::query()->findOrFail($medicalRecord->doctor_id);
        $signedAt = $medicalRecord->fresh()->getRawOriginal('signed_at');

        $serviceOccurredAt = now()->subHours(5)->format('Y-m-d\\TH:i');
        $response = $this->actingAs($doctor)->post(route('downtime-reconciliations.store', [
            $medicalRecord->patient,
            $medicalRecord,
        ]), [
            'paper_reference' => 'DT-2026-00042',
            'service_occurred_at' => $serviceOccurredAt,
        ]);

        $response->assertRedirect(route('pasien.show', $medicalRecord->patient));
        $this->assertDatabaseHas('downtime_reconciliations', [
            'medical_record_id' => $medicalRecord->id,
            'entered_by_user_id' => $doctor->id,
            'paper_reference' => 'DT-2026-00042',
            'status' => 'pending_review',
        ]);
        $this->assertDatabaseHas('downtime_reconciliation_events', [
            'event_type' => 'created',
            'to_status' => 'pending_review',
            'actor_user_id' => $doctor->id,
        ]);
        $this->assertSame($signedAt, $medicalRecord->fresh()->getRawOriginal('signed_at'));
        $storedServiceTime = DowntimeReconciliation::query()->firstOrFail()->service_occurred_at;
        $expectedServiceTime = Carbon::parse($serviceOccurredAt, config('app.timezone'));
        $this->assertSame($expectedServiceTime->timestamp, $storedServiceTime->timestamp);
    }

    public function test_only_original_author_can_link_a_downtime_form_to_their_final_record(): void
    {
        $medicalRecord = MedicalRecord::factory()->create();
        $otherDoctor = User::factory()->create(['role' => 'dokter']);

        $this->actingAs($otherDoctor)
            ->post(route('downtime-reconciliations.store', [$medicalRecord->patient, $medicalRecord]), [
                'paper_reference' => 'DT-2026-00043',
                'service_occurred_at' => now()->subHour()->format('Y-m-d\\TH:i'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('downtime_reconciliations', 0);
    }

    public function test_paper_reference_is_unique_and_future_service_time_is_rejected(): void
    {
        $medicalRecord = MedicalRecord::factory()->create();
        $doctor = User::query()->findOrFail($medicalRecord->doctor_id);
        $data = [
            'paper_reference' => 'DT-2026-00044',
            'service_occurred_at' => now()->subHour()->format('Y-m-d\\TH:i'),
        ];

        $this->actingAs($doctor)->post(route('downtime-reconciliations.store', [$medicalRecord->patient, $medicalRecord]), $data)->assertRedirect();
        $this->actingAs($doctor)->post(route('downtime-reconciliations.store', [$medicalRecord->patient, $medicalRecord]), $data)->assertSessionHasErrors('paper_reference');

        $this->actingAs($doctor)->post(route('downtime-reconciliations.store', [$medicalRecord->patient, $medicalRecord]), [
            'paper_reference' => 'DT-2026-00045',
            'service_occurred_at' => now()->addHour()->format('Y-m-d\\TH:i'),
        ])->assertSessionHasErrors('service_occurred_at');

        $this->assertDatabaseCount('downtime_reconciliations', 1);
    }

    public function test_pmik_can_review_and_reconcile_discrepancy_with_append_only_event_history(): void
    {
        $reconciliation = DowntimeReconciliation::factory()->create();
        $pmik = User::factory()->create(['role' => 'perekam_medis']);

        $this->actingAs($pmik)->get(route('downtime-reconciliations.index'))
            ->assertOk()
            ->assertSee($reconciliation->paper_reference);

        $this->actingAs($pmik)->post(route('downtime-reconciliations.update', $reconciliation), [
            'status' => 'discrepancy',
            'note' => 'Nomor formulir sudah cocok, waktu perlu klarifikasi dengan unit pelayanan.',
        ])->assertRedirect(route('downtime-reconciliations.index'));

        $this->actingAs($pmik)->post(route('downtime-reconciliations.update', $reconciliation), [
            'status' => 'reconciled',
            'note' => 'Waktu dikonfirmasi pada formulir asli dan sudah sesuai.',
        ])->assertRedirect(route('downtime-reconciliations.index'));

        $this->assertDatabaseHas('downtime_reconciliations', [
            'id' => $reconciliation->id,
            'status' => 'reconciled',
            'reviewed_by_user_id' => $pmik->id,
        ]);
        $this->assertDatabaseCount('downtime_reconciliation_events', 3);
        $this->assertSame(
            ['pending_review', 'discrepancy', 'reconciled'],
            DB::table('downtime_reconciliation_events')
                ->where('downtime_reconciliation_id', $reconciliation->id)
                ->orderBy('id')
                ->pluck('to_status')
                ->all(),
        );
    }

    public function test_non_pmik_cannot_open_or_change_the_review_queue(): void
    {
        $reconciliation = DowntimeReconciliation::factory()->create();
        $doctor = User::query()->findOrFail($reconciliation->medicalRecord->doctor_id);

        $this->actingAs($doctor)->get(route('downtime-reconciliations.index'))->assertForbidden();
        $this->actingAs($doctor)->post(route('downtime-reconciliations.update', $reconciliation), [
            'status' => 'reconciled',
            'note' => 'Tidak semestinya dapat disimpan.',
        ])->assertForbidden();

        $this->assertSame('pending_review', $reconciliation->fresh()->status);
        $this->assertDatabaseCount('downtime_reconciliation_events', 1);
    }

    public function test_database_prevents_mutating_downtime_event_history(): void
    {
        $reconciliation = DowntimeReconciliation::factory()->create();
        $eventId = DB::table('downtime_reconciliation_events')
            ->where('downtime_reconciliation_id', $reconciliation->id)
            ->value('id');

        $this->expectException(QueryException::class);
        DB::table('downtime_reconciliation_events')->where('id', $eventId)->update(['event_note' => 'mutasi']);
    }
}
