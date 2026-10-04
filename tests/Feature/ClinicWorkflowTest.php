<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\Icd10Code;
use App\Models\IntegrationConfig;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_workflow_runs_from_registration_through_medicine_dispensing(): void
    {
        $frontDesk = User::factory()->create(['role' => 'pendaftaran']);
        $nurse = User::factory()->create(['role' => 'perawat']);
        $doctor = User::factory()->create(['role' => 'dokter']);
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $medicine = Medicine::factory()->create(['stok' => 8]);
        $diagnosis = Icd10Code::query()->create([
            'code' => 'J06.9',
            'name_en' => 'Acute upper respiratory infection, unspecified',
            'name_id' => 'Infeksi saluran pernapasan atas akut',
            'is_common' => true,
        ]);

        $this->actingAs($frontDesk)->post(route('antrean.store'), [
            'identitas_tidak_diketahui' => '0',
            'nama_lengkap' => 'Pasien Uji Alur',
            'nik' => '3175091201900001',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '1990-12-12',
            'poli' => 'umum',
            'keluhan_singkat' => 'Demam sejak kemarin',
        ])->assertRedirect(route('dashboard'));

        $queue = Queue::query()->sole();
        $this->assertSame(QueueStatus::WaitingForTriage->value, $queue->status);

        $this->actingAs($nurse)->post(route('triage.ttv', $queue), [
            'sistolik' => 120,
            'diastolik' => 80,
            'suhu' => 37.2,
            'nadi' => 78,
            'berat_badan' => 62,
            'tinggi_badan' => 165,
        ])->assertRedirect(route('dashboard'));

        $this->actingAs($doctor)->post(route('antrean.call', $queue))->assertRedirect(route('periksa.show', $queue));
        $this->assertSame(QueueStatus::UnderExamination->value, $queue->fresh()->status);

        $this->actingAs($doctor)->post(route('periksa.finish', $queue), [
            'subjective' => 'Demam dan pilek sejak satu hari.',
            'objective' => 'Tanda vital dicatat saat triase.',
            'assessment' => 'Infeksi saluran pernapasan atas akut.',
            'plan' => 'Terapi simptomatik dan edukasi tanda bahaya.',
            'icd10_primary_code' => $diagnosis->code,
            'disposition' => 'home',
            'terbitkan_surat_sakit' => true,
            'lama_hari_sakit' => 2,
            'tgl_mulai_sakit' => today()->toDateString(),
            'medicines' => [[
                'medicine_id' => $medicine->id,
                'jumlah' => 3,
                'aturan_pakai' => 'Satu tablet tiga kali sehari sesudah makan',
            ]],
        ])->assertRedirect(route('dashboard'));

        $queue->refresh();
        $this->assertSame(QueueStatus::WaitingForPharmacy->value, $queue->status);
        $this->assertDatabaseHas('medical_records', [
            'queue_id' => $queue->id,
            'status' => 'final',
            'icd10_primary_code' => $diagnosis->code,
        ]);

        $patient = $queue->load('patient')->patient;
        $this->actingAs($frontDesk)->get(route('antrean.print', $queue))->assertOk();
        $this->get(route('pasien.card', $patient))->assertOk();
        $this->actingAs($doctor)->get(route('pasien.show', $patient))->assertOk();
        $letter = $patient->letters()->sole();
        $this->get(route('letter.print', $letter))->assertOk()->assertSee('DRAF — dokumen internal');

        $prescription = $queue->prescription;
        $this->assertNotNull($prescription);

        $this->actingAs($pharmacist)->post(route('farmasi.verify', $prescription))->assertRedirect();
        $this->assertSame('processing', $prescription->fresh()->status);
        $this->actingAs($pharmacist)->post(route('farmasi.ready', $prescription))->assertRedirect();
        $this->assertSame('ready', $prescription->fresh()->status);
        $this->actingAs($pharmacist)->post(route('farmasi.dispense', $prescription))->assertRedirect();

        $this->assertSame('dispensed', $prescription->fresh()->status);
        $this->assertSame(QueueStatus::Completed->value, $queue->fresh()->status);
        $this->assertSame(5, $medicine->fresh()->stok);
        $this->assertDatabaseHas('prescription_items', [
            'prescription_id' => $prescription->id,
            'medicine_id' => $medicine->id,
            'jumlah' => 3,
            'is_dispensed' => true,
        ]);
        $this->assertDatabaseHas('medicine_stock_movements', [
            'medicine_id' => $medicine->id,
            'movement_type' => 'dispense',
            'quantity' => 3,
            'stock_before' => 8,
            'stock_after' => 5,
            'reference' => $prescription->nomor_resep,
        ]);
        $this->actingAs($pharmacist)->get(route('farmasi.etiket', $prescription))->assertOk();
    }

    public function test_rbac_blocks_administrative_and_pharmacy_accounts_from_clinical_data(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $frontDesk = User::factory()->create(['role' => 'pendaftaran']);
        $patient = Patient::query()->create([
            'no_rm' => 'RM-TEST-0001',
            'nama_lengkap' => 'Pasien Privasi',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1990-01-01',
            'jenis_pasien' => 'Umum',
            'riwayat_alergi' => 'Amoxicillin',
        ]);

        $this->actingAs($administrator)->get(route('pasien.index'))->assertForbidden();
        $this->actingAs($administrator)->get(route('triage.index'))->assertForbidden();
        $this->get(route('password.edit'))->assertOk();
        $this->actingAs($pharmacist)->get(route('pasien.index'))->assertForbidden();
        $this->actingAs($pharmacist)->get(route('triage.index'))->assertForbidden();
        $this->actingAs($pharmacist)->get(route('admin.index'))->assertForbidden();
        $this->actingAs($frontDesk)->get(route('pasien.show', $patient))->assertForbidden();
        $this->get(route('pasien.index'))->assertOk()->assertDontSee('Amoxicillin');

        $frontDesk->update(['is_active' => false]);
        $this->actingAs($frontDesk)->get(route('password.edit'))->assertForbidden();
    }

    public function test_each_role_can_open_its_operational_workspace(): void
    {
        $frontDesk = User::factory()->create(['role' => 'pendaftaran']);
        $nurse = User::factory()->create(['role' => 'perawat']);
        $doctor = User::factory()->create(['role' => 'dokter']);
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $manager = User::factory()->create(['role' => 'kepala_klinik']);
        $administrator = User::factory()->create(['role' => 'admin']);
        $patient = Patient::query()->create([
            'no_rm' => 'RM-TEST-0002',
            'nama_lengkap' => 'Pasien Tampilan',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '1990-01-01',
            'jenis_pasien' => 'Umum',
        ]);

        $this->actingAs($frontDesk)->get(route('dashboard'))->assertOk();
        $this->get(route('pasien.index'))->assertOk()->assertSee('Pasien Tampilan');

        $this->actingAs($nurse)->get(route('triage.index'))->assertOk();
        $this->get(route('pasien.show', $patient))->assertForbidden();

        $this->actingAs($doctor)->get(route('periksa.show'))->assertOk();
        $this->get(route('pasien.index'))->assertOk();

        $this->actingAs($pharmacist)->get(route('farmasi.index'))->assertOk();
        $this->actingAs($manager)->get(route('manajemen.index'))->assertOk();
        $this->actingAs($administrator)->get(route('admin.index'))->assertOk();
    }

    public function test_pharmacy_cannot_verify_a_prescription_when_stock_is_insufficient(): void
    {
        $frontDesk = User::factory()->create(['role' => 'pendaftaran']);
        $nurse = User::factory()->create(['role' => 'perawat']);
        $doctor = User::factory()->create(['role' => 'dokter']);
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $medicine = Medicine::factory()->create(['stok' => 1]);
        $diagnosis = Icd10Code::query()->create([
            'code' => 'R50.9',
            'name_en' => 'Fever, unspecified',
            'name_id' => 'Demam, tidak spesifik',
        ]);

        $this->actingAs($frontDesk)->post(route('antrean.store'), [
            'identitas_tidak_diketahui' => '0',
            'nama_lengkap' => 'Pasien Stok Terbatas',
            'nik' => '3175091201900002',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1990-12-12',
            'poli' => 'umum',
        ])->assertRedirect(route('dashboard'));
        $queue = Queue::query()->sole();
        $this->actingAs($nurse)->post(route('triage.ttv', $queue), [
            'sistolik' => 120,
            'diastolik' => 80,
            'suhu' => 37,
        ])->assertRedirect(route('dashboard'));
        $this->actingAs($doctor)->post(route('antrean.call', $queue))->assertRedirect(route('periksa.show', $queue));
        $this->actingAs($doctor)->post(route('periksa.finish', $queue), [
            'subjective' => 'Keluhan uji.',
            'objective' => 'Pemeriksaan dicatat.',
            'assessment' => 'Observasi klinis.',
            'plan' => 'Terapi sesuai evaluasi.',
            'icd10_primary_code' => $diagnosis->code,
            'disposition' => 'home',
            'medicines' => [[
                'medicine_id' => $medicine->id,
                'jumlah' => 2,
                'aturan_pakai' => 'Sesuai instruksi dokter',
            ]],
        ])->assertRedirect(route('dashboard'));

        $prescription = $queue->fresh()->prescription;
        $this->actingAs($pharmacist)->post(route('farmasi.verify', $prescription))->assertSessionHasErrors('prescription');

        $this->assertSame('pending', $prescription->fresh()->status);
        $this->assertSame(QueueStatus::WaitingForPharmacy->value, $queue->fresh()->status);
        $this->assertSame(1, $medicine->fresh()->stok);
    }

    public function test_reused_patient_identifiers_cannot_silently_match_different_demographics(): void
    {
        $frontDesk = User::factory()->create(['role' => 'pendaftaran']);
        $registration = [
            'identitas_tidak_diketahui' => '0',
            'nama_lengkap' => 'Nama Pertama',
            'nik' => '3175091201900003',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1990-12-12',
            'poli' => 'umum',
        ];

        $this->actingAs($frontDesk)->post(route('antrean.store'), $registration)->assertRedirect(route('dashboard'));
        $this->post(route('antrean.store'), array_merge($registration, ['nama_lengkap' => 'Nama Berbeda']))
            ->assertSessionHasErrors('nik');

        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseCount('queues', 1);
    }

    public function test_integration_credentials_are_encrypted_and_never_activate_the_connection(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $clientSecret = 'SensitiveClientSecret123';

        $this->actingAs($administrator)->post(route('admin.integration.store'), [
            'system_code' => 'CLINIC_API',
            'system_name' => 'Clinic API',
            'organization_id' => 'ORG-001',
            'client_id' => 'client-001',
            'client_secret' => $clientSecret,
            'base_url' => 'https://api.example.test',
            'auth_url' => 'https://auth.example.test',
            'environment' => 'sandbox',
        ])->assertRedirect(route('admin.index', ['tab' => 'integrations']));

        $integration = IntegrationConfig::query()->sole();
        $this->assertSame($clientSecret, $integration->client_secret);
        $this->assertFalse($integration->is_active);
        $this->assertSame('not_configured', $integration->sync_status);
        $this->assertNotSame($clientSecret, $integration->getRawOriginal('client_secret'));
    }

    public function test_administrator_cannot_disable_their_own_last_admin_account(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)->patch(route('admin.user.update', $administrator), [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'role' => 'admin',
            'is_active' => false,
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($administrator->fresh()->is_active);
        $this->assertSame('admin', $administrator->fresh()->role);
    }

    public function test_pharmacy_can_receive_stock_only_into_the_matching_batch(): void
    {
        $pharmacist = User::factory()->create(['role' => 'farmasi']);
        $medicine = Medicine::factory()->create([
            'stok' => 1,
            'nomor_batch' => 'LOT-2026-01',
            'expired_date' => today()->addMonths(6),
        ]);

        $this->actingAs($pharmacist)->get(route('farmasi.index'))->assertOk()->assertSee('Catat penerimaan stok');

        $this->post(route('farmasi.stock.receive', $medicine), [
            'quantity' => 5,
            'reason' => 'Penerimaan dari pemasok.',
            'reference' => 'INV-1001',
            'nomor_batch' => 'LOT-2026-01',
            'expired_date' => today()->addMonths(6)->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(6, $medicine->fresh()->stok);
        $this->assertDatabaseHas('medicine_stock_movements', [
            'medicine_id' => $medicine->id,
            'user_id' => $pharmacist->id,
            'movement_type' => 'receipt',
            'quantity' => 5,
            'stock_before' => 1,
            'stock_after' => 6,
            'reference' => 'INV-1001',
        ]);

        $this->post(route('farmasi.stock.receive', $medicine), [
            'quantity' => 5,
            'reason' => 'Batch berbeda.',
            'nomor_batch' => 'LOT-2026-02',
            'expired_date' => today()->addYear()->toDateString(),
        ])->assertSessionHasErrors('nomor_batch');

        $this->assertSame(6, $medicine->fresh()->stok);
        $this->assertDatabaseCount('medicine_stock_movements', 1);
    }

    public function test_admin_initial_stock_is_recorded_in_the_inventory_ledger(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)->post(route('admin.medicine.store'), [
            'kode_obat' => 'MED-OPEN-001',
            'nama_obat' => 'Obat Pembuka',
            'kategori' => 'Generik',
            'bentuk_sediaan' => 'Tablet',
            'satuan' => 'tablet',
            'stok' => 25,
            'stok_minimum' => 5,
            'nomor_batch' => 'OPEN-LOT-1',
            'expired_date' => today()->addYear()->toDateString(),
            'harga_satuan' => 1000,
        ])->assertRedirect(route('admin.index', ['tab' => 'medicines']));

        $medicine = Medicine::query()->sole();
        $this->assertSame(25, $medicine->stok);
        $this->assertDatabaseHas('medicine_stock_movements', [
            'medicine_id' => $medicine->id,
            'user_id' => $administrator->id,
            'movement_type' => 'opening',
            'quantity' => 25,
            'stock_before' => 0,
            'stock_after' => 25,
        ]);
    }

    public function test_doctor_medicine_search_only_returns_usable_lots_with_batch_details(): void
    {
        $doctor = User::factory()->create(['role' => 'dokter']);
        $usableLot = Medicine::factory()->create([
            'nama_obat' => 'Lot Obat Uji',
            'kode_obat' => 'LOT-SEARCH-01',
            'nomor_batch' => 'SEARCH-BATCH-01',
            'stok' => 10,
            'expired_date' => today()->addMonths(6),
        ]);
        Medicine::factory()->create([
            'nama_obat' => 'Lot Obat Uji Kedaluwarsa',
            'kode_obat' => 'LOT-SEARCH-02',
            'nomor_batch' => 'SEARCH-BATCH-02',
            'expired_date' => today()->subDay(),
        ]);
        Medicine::factory()->create([
            'nama_obat' => 'Lot Obat Uji Kosong',
            'kode_obat' => 'LOT-SEARCH-03',
            'nomor_batch' => 'SEARCH-BATCH-03',
            'stok' => 0,
        ]);

        $this->actingAs($doctor)
            ->getJson(route('api.search.medicines', ['q' => 'Lot Obat Uji']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $usableLot->id)
            ->assertJsonPath('0.nomor_batch', 'SEARCH-BATCH-01')
            ->assertJsonPath('0.kode_obat', 'LOT-SEARCH-01');
    }
}
