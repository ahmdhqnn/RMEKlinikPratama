<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReview;
use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Models\MedicalRecordRevision;
use App\Models\Queue;
use App\Models\User;
use App\Services\MedicalRecordQualityReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordQualityReviewDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_pmik_dashboard_counts_current_review_states_and_overdue_tasks(): void
    {
        $reviewer = User::factory()->create(['role' => 'perekam_medis']);
        $unreviewed = MedicalRecord::factory()->create();
        $complete = MedicalRecord::factory()->create();
        $withFinding = MedicalRecord::factory()->create();
        $stale = MedicalRecord::factory()->create();

        MedicalRecordQualityReview::factory()->create([
            'medical_record_id' => $complete->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_record_version' => 1,
        ]);
        $findingReview = MedicalRecordQualityReview::factory()->create([
            'medical_record_id' => $withFinding->id,
            'reviewed_by_user_id' => $reviewer->id,
            'review_result' => 'needs_follow_up',
            'findings' => 'Kelengkapan catatan perlu ditindaklanjuti.',
            'checklist_json' => ['soap_sections' => 'finding'],
        ]);
        MedicalRecordQualityReviewFollowUp::factory()->create([
            'medical_record_quality_review_id' => $findingReview->id,
            'assigned_to_user_id' => $withFinding->doctor_id,
            'due_at' => today()->subDay()->toDateString(),
        ]);
        MedicalRecordQualityReview::factory()->create([
            'medical_record_id' => $stale->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_record_version' => 1,
        ]);
        MedicalRecordRevision::query()->create([
            'medical_record_id' => $stale->id,
            'version_number' => 2,
            'revision_type' => 'addendum',
            'reason' => 'Revisi sintetis untuk pengujian status dashboard.',
            'snapshot_json' => ['status' => 'final'],
            'addendum_text' => 'Addendum sintetis.',
            'recorded_by_user_id' => $stale->doctor_id,
            'recorded_by_name' => $stale->signed_by_name,
            'recorded_by_role' => 'dokter',
            'origin' => 'application',
            'recorded_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->get(route('quality-reviews.index'))
            ->assertOk()
            ->assertSee('Catatan final')
            ->assertSee('Tugas terlambat');

        $summary = app(MedicalRecordQualityReviewService::class)->qualitySummary();

        $this->assertSame(4, $summary['final_records']);
        $this->assertSame(2, $summary['pending_reviews']);
        $this->assertSame(1, $summary['complete_reviews']);
        $this->assertSame(1, $summary['follow_up_reviews']);
        $this->assertSame(1, $summary['open_follow_ups']);
        $this->assertSame(1, $summary['overdue_follow_ups']);
    }

    public function test_non_pmik_cannot_open_the_quality_dashboard(): void
    {
        $doctor = User::factory()->create(['role' => 'dokter']);

        $this->actingAs($doctor)
            ->get(route('quality-reviews.index'))
            ->assertForbidden();
    }

    public function test_quality_trends_include_zero_months_and_count_reviews_by_local_month(): void
    {
        $reviewer = User::factory()->create(['role' => 'perekam_medis']);
        $currentMonth = now(config('app.timezone'))->startOfMonth()->addDays(2);
        $previousMonth = $currentMonth->copy()->subMonth()->addDays(3);

        MedicalRecordQualityReview::factory()->create([
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => $currentMonth,
        ]);
        MedicalRecordQualityReview::factory()->create([
            'reviewed_by_user_id' => $reviewer->id,
            'review_result' => 'needs_follow_up',
            'findings' => 'Uraian temuan sintetis.',
            'reviewed_at' => $previousMonth,
        ]);

        $trends = app(MedicalRecordQualityReviewService::class)->qualityTrends(3);

        $this->assertCount(3, $trends);
        $this->assertSame(0, $trends[0]['total_reviews']);
        $this->assertSame(1, $trends[1]['total_reviews']);
        $this->assertSame(1, $trends[1]['follow_up_reviews']);
        $this->assertSame(1, $trends[2]['complete_reviews']);
        $this->assertSame($previousMonth->format('Y-m'), $trends[1]['month']);
        $this->assertSame($currentMonth->format('Y-m'), $trends[2]['month']);
    }

    public function test_quality_trends_are_stratified_by_poli_and_include_empty_slots(): void
    {
        $reviewer = User::factory()->create(['role' => 'perekam_medis']);
        $currentMonth = now()->startOfMonth()->addDays(3);
        $previousMonth = $currentMonth->copy()->subMonth()->addDays(3);
        $generalRecord = $this->createQualityRecordForPoli('umum');
        $dentalRecord = $this->createQualityRecordForPoli('gigi');

        MedicalRecordQualityReview::factory()->create([
            'medical_record_id' => $generalRecord->id,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => $currentMonth,
        ]);
        MedicalRecordQualityReview::factory()->create([
            'medical_record_id' => $dentalRecord->id,
            'reviewed_by_user_id' => $reviewer->id,
            'review_result' => 'needs_follow_up',
            'findings' => 'Temuan dokumentasi gigi sintetis.',
            'checklist_json' => ['dental_documentation' => 'finding'],
            'reviewed_at' => $previousMonth,
        ]);

        $trends = app(MedicalRecordQualityReviewService::class)->qualityTrendsByPoli(3);

        $this->assertCount(6, $trends);
        $this->assertSame(0, $trends[0]['total_reviews']);
        $this->assertSame('umum', $trends[2]['poli']);
        $this->assertSame(0, $trends[2]['complete_reviews']);
        $this->assertSame('gigi', $trends[3]['poli']);
        $this->assertSame(1, $trends[3]['follow_up_reviews']);
        $this->assertSame('umum', $trends[4]['poli']);
        $this->assertSame(1, $trends[4]['complete_reviews']);
        $this->assertSame(0, $trends[5]['total_reviews']);
    }

    private function createQualityRecordForPoli(string $poli): MedicalRecord
    {
        $doctor = User::factory()->create(['role' => $poli === 'gigi' ? 'dokter_gigi' : 'dokter']);
        $queue = Queue::factory()->state(['poli' => $poli, 'dokter_id' => $doctor->id])->create();

        return MedicalRecord::factory()->create([
            'queue_id' => $queue->id,
            'patient_id' => $queue->patient_id,
            'doctor_id' => $doctor->id,
            'signed_by_user_id' => $doctor->id,
            'signed_by_role' => $poli === 'gigi' ? 'dokter_gigi' : 'dokter',
        ]);
    }
}
