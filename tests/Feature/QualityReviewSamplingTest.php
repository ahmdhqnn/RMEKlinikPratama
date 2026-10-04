<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\QualityReviewSamplingRun;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QualityReviewSamplingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pmik_creates_a_stratified_auditable_cohort_with_risk_and_routine_samples(): void
    {
        $reviewer = User::factory()->create(['role' => 'perekam_medis']);
        $periodStart = now()->startOfMonth()->subMonth();
        $routineRecords = [];
        foreach (['umum', 'gigi'] as $poli) {
            $highRisk = $this->createFinalRecordInPeriod($periodStart, $poli);
            $routine = $this->createFinalRecordInPeriod($periodStart, $poli);
            $routineRecords[] = $routine;
            MedicalRecordQualityReview::factory()->create([
                'medical_record_id' => $routine->id,
                'reviewed_record_version' => 1,
                'reviewed_by_user_id' => $reviewer->id,
            ]);
            $this->assertNotSame($highRisk->id, $routine->id);
        }

        $this->actingAs($reviewer)->post(route('quality-reviews.sampling.store'), [
            'period' => $periodStart->format('Y-m'),
            'sample_size' => 4,
        ])->assertRedirect(route('quality-reviews.index'));

        $run = QualityReviewSamplingRun::query()->with('items')->firstOrFail();
        $this->assertSame(4, $run->selected_sample_size);
        $this->assertEquals(['gigi' => 2, 'umum' => 2], $run->population_counts_json);
        $this->assertSame(2, $run->items->where('poli', 'umum')->count());
        $this->assertSame(2, $run->items->where('poli', 'gigi')->count());
        $this->assertSame(2, $run->items->where('selection_reason', 'high_risk')->count());
        $this->assertSame(2, $run->items->where('selection_reason', 'routine')->count());
        $this->assertEqualsCanonicalizing(
            collect($routineRecords)->pluck('id')->all(),
            $run->items->where('selection_reason', 'routine')->pluck('medical_record_id')->all(),
        );
        $this->assertDatabaseHas('audit_logs', ['action' => 'CREATE_MEDICAL_RECORD_QUALITY_SAMPLING_RUN']);
        $this->actingAs($reviewer)->get(route('quality-reviews.index'))
            ->assertOk()
            ->assertSee('Cohort sampling mutu bulanan')
            ->assertSee($routineRecords[0]->patient->nama_lengkap);
    }

    public function test_cohort_is_unique_per_month_and_existing_sample_is_not_replaced(): void
    {
        $pmik = User::factory()->create(['role' => 'perekam_medis']);
        $periodStart = now()->startOfMonth()->subMonth();
        $record = $this->createFinalRecordInPeriod($periodStart, 'umum');
        $payload = ['period' => $periodStart->format('Y-m'), 'sample_size' => 1];

        $this->actingAs($pmik)->post(route('quality-reviews.sampling.store'), $payload)->assertRedirect();
        $runId = QualityReviewSamplingRun::query()->value('id');
        $this->actingAs($pmik)->post(route('quality-reviews.sampling.store'), $payload)->assertSessionHasErrors('period');

        $this->assertSame($runId, QualityReviewSamplingRun::query()->value('id'));
        $this->assertDatabaseHas('quality_review_sampling_items', ['medical_record_id' => $record->id]);
        $this->assertDatabaseCount('quality_review_sampling_runs', 1);
    }

    public function test_only_pmik_can_create_sampling_cohort(): void
    {
        $doctor = User::factory()->create(['role' => 'dokter']);
        $periodStart = now()->startOfMonth()->subMonth();
        $this->createFinalRecordInPeriod($periodStart, 'umum');

        $this->actingAs($doctor)->post(route('quality-reviews.sampling.store'), [
            'period' => $periodStart->format('Y-m'),
            'sample_size' => 1,
        ])->assertForbidden();

        $this->assertDatabaseCount('quality_review_sampling_runs', 0);
    }

    public function test_empty_period_and_future_period_are_rejected(): void
    {
        $pmik = User::factory()->create(['role' => 'perekam_medis']);
        $emptyPeriod = now()->startOfMonth()->subMonths(2);

        $this->actingAs($pmik)->post(route('quality-reviews.sampling.store'), [
            'period' => $emptyPeriod->format('Y-m'),
            'sample_size' => 5,
        ])->assertSessionHasErrors('period');

        $this->actingAs($pmik)->post(route('quality-reviews.sampling.store'), [
            'period' => now()->addMonth()->format('Y-m'),
            'sample_size' => 5,
        ])->assertSessionHasErrors('period');

        $this->assertDatabaseCount('quality_review_sampling_runs', 0);
    }

    public function test_sampling_history_cannot_be_mutated_directly(): void
    {
        $run = QualityReviewSamplingRun::factory()->create();
        $record = MedicalRecord::factory()->create();
        $item = $run->items()->create([
            'medical_record_id' => $record->id,
            'poli' => 'umum',
            'selection_reason' => 'routine',
            'selected_at' => now(),
        ]);

        try {
            DB::transaction(static function () use ($item): void {
                DB::table('quality_review_sampling_items')->where('id', $item->id)->delete();
            });
            $this->fail('Sampling items must remain immutable.');
        } catch (QueryException) {
            $this->assertDatabaseHas('quality_review_sampling_items', ['id' => $item->id]);
        }

        $this->expectException(QueryException::class);
        DB::table('quality_review_sampling_runs')->where('id', $run->id)->update(['requested_sample_size' => 1]);
    }

    private function createFinalRecordInPeriod($periodStart, string $poli): MedicalRecord
    {
        $doctor = User::factory()->create(['role' => $poli === 'gigi' ? 'dokter_gigi' : 'dokter']);
        $queue = Queue::factory()->state(['poli' => $poli, 'dokter_id' => $doctor->id])->create();

        return MedicalRecord::factory()->create([
            'queue_id' => $queue->id,
            'patient_id' => $queue->patient_id,
            'doctor_id' => $queue->dokter_id,
            'signed_by_user_id' => $queue->dokter_id,
            'signed_by_role' => $poli === 'gigi' ? 'dokter_gigi' : 'dokter',
            'signed_at' => $periodStart->copy()->addDays(3)->setTime(10, 0),
        ]);
    }
}
