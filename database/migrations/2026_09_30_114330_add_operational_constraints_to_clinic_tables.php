<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->string('nik_hash', 64)->nullable()->unique();
            $table->unique('nip');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_valid CHECK (role IN ('admin', 'pendaftaran', 'perawat', 'dokter', 'dokter_gigi', 'farmasi', 'kepala_klinik'))");
        DB::statement("ALTER TABLE patients ALTER COLUMN jenis_pasien SET DEFAULT 'Umum'");
        DB::statement('ALTER TABLE patients ALTER COLUMN jenis_kelamin DROP DEFAULT');

        Schema::table('queues', function (Blueprint $table): void {
            $table->unique(['tanggal_antrean', 'poli', 'nomor_antrean']);
        });

        Schema::table('vital_signs', function (Blueprint $table): void {
            $table->unique('queue_id');
        });

        Schema::table('medical_records', function (Blueprint $table): void {
            $table->unique('queue_id');
        });

        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->unique('queue_id');
        });

        Schema::table('odontograms', function (Blueprint $table): void {
            $table->unique('patient_id');
        });
        DB::statement('ALTER TABLE odontograms ALTER COLUMN oklusi DROP DEFAULT, ALTER COLUMN oklusi DROP NOT NULL');
        DB::statement('ALTER TABLE odontograms ALTER COLUMN torus_palatinus DROP DEFAULT, ALTER COLUMN torus_palatinus DROP NOT NULL');
        DB::statement('ALTER TABLE odontograms ALTER COLUMN torus_mandibularis DROP DEFAULT, ALTER COLUMN torus_mandibularis DROP NOT NULL');
        DB::statement('ALTER TABLE odontograms ALTER COLUMN palatum DROP DEFAULT, ALTER COLUMN palatum DROP NOT NULL');
        DB::statement('ALTER TABLE odontograms ALTER COLUMN diastema DROP DEFAULT, ALTER COLUMN diastema DROP NOT NULL');

        Schema::create('queue_sequences', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal_antrean');
            $table->string('poli', 20);
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['tanggal_antrean', 'poli']);
        });

        DB::statement('ALTER TABLE integration_configs ALTER COLUMN is_active SET DEFAULT false');
        DB::statement("ALTER TABLE integration_configs ALTER COLUMN sync_status SET DEFAULT 'not_configured'");
        DB::statement("ALTER TABLE medical_records ALTER COLUMN satusehat_sync_status SET DEFAULT 'not_configured'");
        DB::statement('ALTER TABLE medicines ADD CONSTRAINT medicines_stock_non_negative CHECK (stok >= 0)');
        DB::statement('ALTER TABLE prescription_items ADD CONSTRAINT prescription_items_quantity_positive CHECK (jumlah > 0)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_audit_log_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Audit logs are append-only';
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS audit_logs_append_only ON audit_logs;
            CREATE TRIGGER audit_logs_append_only
            BEFORE UPDATE OR DELETE ON audit_logs
            FOR EACH ROW EXECUTE FUNCTION prevent_audit_log_mutation();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_append_only ON audit_logs');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_audit_log_mutation()');
        DB::statement('ALTER TABLE prescription_items DROP CONSTRAINT IF EXISTS prescription_items_quantity_positive');
        DB::statement('ALTER TABLE medicines DROP CONSTRAINT IF EXISTS medicines_stock_non_negative');
        DB::statement("ALTER TABLE medical_records ALTER COLUMN satusehat_sync_status SET DEFAULT 'synced'");
        DB::statement("ALTER TABLE integration_configs ALTER COLUMN sync_status SET DEFAULT 'connected'");
        DB::statement('ALTER TABLE integration_configs ALTER COLUMN is_active SET DEFAULT true');
        DB::statement("ALTER TABLE patients ALTER COLUMN jenis_pasien SET DEFAULT 'PNS Pusat'");
        DB::statement("ALTER TABLE patients ALTER COLUMN jenis_kelamin SET DEFAULT 'L'");
        DB::statement("ALTER TABLE odontograms ALTER COLUMN oklusi SET DEFAULT 'Normal Bite', ALTER COLUMN oklusi SET NOT NULL");
        DB::statement("ALTER TABLE odontograms ALTER COLUMN torus_palatinus SET DEFAULT 'Tidak Ada', ALTER COLUMN torus_palatinus SET NOT NULL");
        DB::statement("ALTER TABLE odontograms ALTER COLUMN torus_mandibularis SET DEFAULT 'Tidak Ada', ALTER COLUMN torus_mandibularis SET NOT NULL");
        DB::statement("ALTER TABLE odontograms ALTER COLUMN palatum SET DEFAULT 'Sedang', ALTER COLUMN palatum SET NOT NULL");
        DB::statement("ALTER TABLE odontograms ALTER COLUMN diastema SET DEFAULT 'Tidak Ada', ALTER COLUMN diastema SET NOT NULL");
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_valid');

        Schema::dropIfExists('queue_sequences');
        Schema::table('odontograms', function (Blueprint $table): void {
            $table->dropUnique(['patient_id']);
        });
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropUnique(['queue_id']);
        });
        Schema::table('medical_records', function (Blueprint $table): void {
            $table->dropUnique(['queue_id']);
        });
        Schema::table('vital_signs', function (Blueprint $table): void {
            $table->dropUnique(['queue_id']);
        });
        Schema::table('queues', function (Blueprint $table): void {
            $table->dropUnique(['tanggal_antrean', 'poli', 'nomor_antrean']);
        });
        Schema::table('patients', function (Blueprint $table): void {
            $table->dropUnique(['nip']);
            $table->dropUnique(['nik_hash']);
            $table->dropColumn('nik_hash');
        });
    }
};
