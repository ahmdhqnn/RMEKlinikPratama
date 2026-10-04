<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE medical_records DROP CONSTRAINT IF EXISTS medical_records_satusehat_sync_status_check');
        DB::statement("ALTER TABLE medical_records ADD CONSTRAINT medical_records_satusehat_sync_status_check CHECK (satusehat_sync_status IN ('not_configured', 'pending', 'synced', 'failed'))");
        DB::statement("ALTER TABLE medical_records ALTER COLUMN satusehat_sync_status SET DEFAULT 'not_configured'");
    }

    public function down(): void
    {
        DB::statement("UPDATE medical_records SET satusehat_sync_status = 'pending' WHERE satusehat_sync_status = 'not_configured'");
        DB::statement("ALTER TABLE medical_records ALTER COLUMN satusehat_sync_status SET DEFAULT 'pending'");
        DB::statement('ALTER TABLE medical_records DROP CONSTRAINT IF EXISTS medical_records_satusehat_sync_status_check');
        DB::statement("ALTER TABLE medical_records ADD CONSTRAINT medical_records_satusehat_sync_status_check CHECK (satusehat_sync_status IN ('pending', 'synced', 'failed'))");
    }
};
