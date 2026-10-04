<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordQualityReviewFollowUp;
use App\Models\MedicalRecordRevision;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use App\Services\MedicalRecordQualityReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalRecordQualityReviewFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_pmik_review_creates_one_assigned_follow_up_per_finding_and_audit_event(): void
    {
        $record = MedicalRecord::factory()->create();
        $reviewer = User::factory()->create(['role' => 'perekam_medis']);
        $dueAt = today()->addDays(4)->toDateString();

        $this->actingAs($reviewer)->post(route('quality-reviews.store', $record), [
            'review_result' => 'needs_follow_up',
            'checklist' => $this->checklistWithFinding(),
            'finding_notes' => ['soap_sections' => 'Bagian asesmen perlu dilengkapi oleh dokter.'],
            'follow_up_due_at' => $dueAt,
        ])->assertRedirect(route('quality-reviews.show', $record));

        $review = $record->qualityReviews()->sole();
        $task = MedicalRecordQualityReviewFollowUp::query()->sole();
        $this->assertSame('needs_follow_up', $review->review_result);
        $this->assertSame(1, $review->reviewed_record_version);
        $this->assertSame('soap_sections', $task->item_key);
        $this->assertSame('Catatan subjektif, objektif, asesmen, dan rencana terdokumentasi', $task->item_label);
        $this->assertSame($record->doctor_id, $task->assigned_to_user_id);
        $this->assertSame($dueAt, $task->due_at->toDateString());
        $this->assertSame('open', $task->status->value);
        $this->assertDatabaseHas('medical_record_quality_review_follow_up_events', [
            'medical_record_quality_review_follow_up_id' => $task->id,
            'event_type' => 'created',
            'to_status' => 'open',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $reviewer->id,
            'action' => 'CREATE_MEDICAL_RECORD_QUALITY_FOLLOW_UP',
            'patient_id' => $record->patient_id,
        ]);
    }

    public function test_review_rejects_a_finding_without_description_and_creates_no_review(): void
    {
        $record = MedicalRecord::factory()->create();
        $reviewer = User::factory()->create(['role' => 'perekam_medis']);

        $this->actingAs($reviewer)->post(route('quality-reviews.store', $record), [
            'review_result' => 'needs_follow_up',
            'checklist' => $this->checklistWithFinding(),
            'follow_up_due_at' => today()->addDays(4)->toDateString(),
        ])->assertSessionHasErrors('finding_notes.soap_sections');

        $this->assertDatabaseCount('medical_record_quality_reviews', 0);
        $this->assertDatabaseCount('medical_record_quality_review_follow_ups', 0);
    }

    public function test_dental_review_assigns_odontogram_finding_to_the_record_author(): void
    {
        $dentist = User::factory()->create(['role' => 'dokter_gigi']);
        $patient = Patient::factory()->create();
        $queue = Queue::factory()->for($patient)->create([
            'dokter_id' => $dentist->id,
            'poli' => 'gigi',
        ]);
        $record = MedicalRecord::factory()->create([
            'queue_id' => $queue->id,
            'patient_id' => $patient->id,
            'doctor_id' => $dentist->id,
            'signed_by_user_id' => $dentist->id,
            'signed_by_name' => $dentist->name,
            'signed_by_role' => 'dokter_gigi',
        ]);
        $reviewer = User::factory()->create(['role' => 'perekam_medis']);
        $checklist = $this->checklistWithFinding();
        $checklist['soap_sections'] = 'pass';
        $checklist['dental_documentation'] = 'finding';

        $this->actingAs($reviewer)->post(route('quality-reviews.store', $record), [
            'review_result' => 'needs_follow_up',
            'checklist' => $checklist,
            'finding_notes' => ['dental_documentation' => 'Odontogram perlu dilengkapi untuk elemen yang diperiksa.'],
            'follow_up_due_at' => today()->addDays(4)->toDateString(),
        ])->assertRedirect(route('quality-reviews.show', $record));

        $this->assertDatabaseHas('medical_record_quality_review_follow_ups', [
            'item_key' => 'dental_documentation',
            'assigned_to_user_id' => $dentist->id,
        ]);
    }

    public function test_doctor_cannot_close_a_finding_before_a_new_record_version_exists(): void
    {
        $task = MedicalRecordQualityReviewFollowUp::factory()->create();
        $doctor = $task->assignee;

        $this->actingAs($doctor)->post(route('quality-review-follow-ups.update', $task), [
            'status' => 'resolved',
            'note' => 'Dokumen sudah ditinjau.',
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseHas('medical_record_quality_review_follow_ups', [
            'id' => $task->id,
            'status' => 'open',
            'resolved_record_version' => null,
        ]);
        $this->assertDatabaseCount('medical_record_quality_review_follow_up_events', 0);
    }

    public function test_assigned_doctor_closes_a_finding_against_the_new_record_version(): void
    {
        $task = MedicalRecordQualityReviewFollowUp::factory()->create();
        $doctor = $task->assignee;
        $record = $task->qualityReview->medicalRecord;

        MedicalRecordRevision::query()->create([
            'medical_record_id' => $record->id,
            'version_number' => 2,
            'revision_type' => 'addendum',
            'reason' => 'Konteks dokumentasi telah diperjelas.',
            'snapshot_json' => ['status' => 'final'],
            'addendum_text' => 'Tambahan catatan yang telah ditinjau dokter.',
            'recorded_by_user_id' => $doctor->id,
            'recorded_by_name' => $doctor->name,
            'recorded_by_role' => $doctor->role,
            'origin' => 'application',
            'recorded_at' => now(),
        ]);

        $this->actingAs($doctor)->post(route('quality-review-follow-ups.update', $task), [
            'status' => 'resolved',
            'note' => 'Addendum v2 sudah ditambahkan.',
        ])->assertRedirect(route('quality-review-follow-ups.index'));

        $this->assertDatabaseHas('medical_record_quality_review_follow_ups', [
            'id' => $task->id,
            'status' => 'resolved',
            'resolved_record_version' => 2,
            'resolved_by_user_id' => $doctor->id,
            'resolution_note' => 'Addendum v2 sudah ditambahkan.',
        ]);
        $this->assertDatabaseHas('medical_record_quality_review_follow_up_events', [
            'medical_record_quality_review_follow_up_id' => $task->id,
            'from_status' => 'open',
            'to_status' => 'resolved',
            'actor_user_id' => $doctor->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $doctor->id,
            'action' => 'UPDATE_MEDICAL_RECORD_QUALITY_FOLLOW_UP',
            'patient_id' => $record->patient_id,
        ]);
    }

    public function test_assigned_doctor_can_mark_a_finding_not_applicable_with_a_reason(): void
    {
        $task = MedicalRecordQualityReviewFollowUp::factory()->create();
        $doctor = $task->assignee;

        $this->actingAs($doctor)->post(route('quality-review-follow-ups.update', $task), [
            'status' => 'not_applicable',
            'note' => 'Pemeriksaan yang dimaksud tidak relevan pada kunjungan ini; alasan dicatat setelah konfirmasi klinis.',
        ])->assertRedirect(route('quality-review-follow-ups.index'));

        $this->assertDatabaseHas('medical_record_quality_review_follow_ups', [
            'id' => $task->id,
            'status' => 'not_applicable',
            'resolved_by_user_id' => $doctor->id,
            'resolved_record_version' => null,
        ]);
        $this->assertDatabaseHas('medical_record_quality_review_follow_up_events', [
            'medical_record_quality_review_follow_up_id' => $task->id,
            'to_status' => 'not_applicable',
            'actor_user_id' => $doctor->id,
        ]);
    }

    public function test_referral_records_receive_the_referral_quality_checklist_item(): void
    {
        $record = MedicalRecord::factory()->create(['disposition' => 'referral']);

        $items = app(MedicalRecordQualityReviewService::class)->checklistItems($record);

        $this->assertArrayHasKey('referral_document', $items);
    }

    public function test_unassigned_doctor_cannot_update_a_follow_up(): void
    {
        $task = MedicalRecordQualityReviewFollowUp::factory()->create();
        $otherDoctor = User::factory()->create(['role' => 'dokter']);

        $this->actingAs($otherDoctor)->post(route('quality-review-follow-ups.update', $task), [
            'status' => 'in_progress',
            'note' => 'Saya mulai mengerjakan temuan ini.',
        ])->assertForbidden();

        $this->assertDatabaseHas('medical_record_quality_review_follow_ups', [
            'id' => $task->id,
            'status' => 'open',
        ]);
    }

    public function test_pmik_can_review_follow_ups_but_cannot_close_a_clinical_task(): void
    {
        $task = MedicalRecordQualityReviewFollowUp::factory()->create();
        $pmik = User::factory()->create(['role' => 'perekam_medis']);

        $this->actingAs($pmik)->get(route('quality-review-follow-ups.index'))->assertSee($task->finding);
        $this->post(route('quality-review-follow-ups.update', $task), [
            'status' => 'not_applicable',
            'note' => 'Temuan sudah ditinjau.',
        ])->assertForbidden();

        $this->assertDatabaseHas('medical_record_quality_review_follow_ups', [
            'id' => $task->id,
            'status' => 'open',
        ]);
    }

    public function test_doctor_only_sees_follow_ups_assigned_to_their_account(): void
    {
        $visibleTask = MedicalRecordQualityReviewFollowUp::factory()->create(['finding' => 'TEMUAN-TUGAS-A']);
        $otherTask = MedicalRecordQualityReviewFollowUp::factory()->create(['finding' => 'TEMUAN-TUGAS-B']);

        $this->actingAs($visibleTask->assignee)
            ->get(route('quality-review-follow-ups.index'))
            ->assertSee('TEMUAN-TUGAS-A')
            ->assertDontSee('TEMUAN-TUGAS-B');
    }

    /** @return array<string, string> */
    private function checklistWithFinding(): array
    {
        return [
            'patient_identity' => 'pass',
            'encounter_context' => 'pass',
            'soap_sections' => 'finding',
            'coded_diagnosis' => 'pass',
            'signer_metadata' => 'pass',
            'disposition' => 'pass',
        ];
    }
}
