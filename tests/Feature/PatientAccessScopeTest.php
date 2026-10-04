<?php

namespace Tests\Feature;

use App\Models\MedicalAttachment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctors_can_only_search_patients_with_an_encounter_assigned_to_them(): void
    {
        $assignedDoctor = User::factory()->create(['role' => 'dokter']);
        $unassignedDoctor = User::factory()->create(['role' => 'dokter']);
        $patient = Patient::factory()->create(['nama_lengkap' => 'Pasien dengan akses terikat']);
        $this->createAssignedRecord($patient, $assignedDoctor, 'umum');

        $this->actingAs($assignedDoctor)
            ->getJson(route('api.search.patients', ['q' => 'Pasien dengan']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['nama_lengkap' => 'Pasien dengan akses terikat']);

        $this->actingAs($unassignedDoctor)
            ->getJson(route('api.search.patients', ['q' => 'Pasien dengan']))
            ->assertOk()
            ->assertExactJson([]);

        $this->actingAs($unassignedDoctor)->get(route('pasien.show', $patient))->assertForbidden();
    }

    public function test_patient_history_only_contains_the_viewing_doctors_own_final_records(): void
    {
        $firstDoctor = User::factory()->create(['role' => 'dokter']);
        $secondDoctor = User::factory()->create(['role' => 'dokter']);
        $patient = Patient::factory()->create();
        $firstRecord = $this->createAssignedRecord($patient, $firstDoctor, 'umum', 'Catatan yang berwenang dibaca.');
        $this->createAssignedRecord($patient, $secondDoctor, 'umum', 'Catatan privat dokter lain.');

        $this->actingAs($firstDoctor)
            ->get(route('pasien.show', $patient))
            ->assertOk()
            ->assertSee('Catatan yang berwenang dibaca.')
            ->assertDontSee('Catatan privat dokter lain.');

        $this->assertDatabaseHas('medical_records', ['id' => $firstRecord->id]);
    }

    public function test_attachment_download_requires_assignment_to_the_patient(): void
    {
        $uploader = User::factory()->create(['role' => 'dokter']);
        $viewer = User::factory()->create(['role' => 'dokter']);
        $patient = Patient::factory()->create();
        $this->createAssignedRecord($patient, $viewer, 'umum');
        $uploaderRecord = $this->createAssignedRecord($patient, $uploader, 'umum');
        $attachment = MedicalAttachment::query()->create([
            'patient_id' => $patient->id,
            'medical_record_id' => $uploaderRecord->id,
            'uploaded_by_user_id' => $uploader->id,
            'kategori' => 'lab_luar',
            'judul_dokumen' => 'Hasil lab privat',
            'file_path' => 'medical-attachments/private.pdf',
            'file_type' => 'pdf',
            'file_size_kb' => 1,
        ]);

        $this->actingAs($viewer)
            ->get(route('pasien.attachment.download', [$patient, $attachment]))
            ->assertForbidden();
    }

    private function createAssignedRecord(Patient $patient, User $doctor, string $poli, string $subjective = 'Keluhan sintetis untuk pengujian.'): MedicalRecord
    {
        $queue = Queue::factory()->create([
            'patient_id' => $patient->id,
            'dokter_id' => $doctor->id,
            'poli' => $poli,
        ]);

        return MedicalRecord::factory()->create([
            'queue_id' => $queue->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'signed_by_user_id' => $doctor->id,
            'signed_by_role' => $poli === 'gigi' ? 'dokter_gigi' : 'dokter',
            'subjective' => $subjective,
        ]);
    }
}
