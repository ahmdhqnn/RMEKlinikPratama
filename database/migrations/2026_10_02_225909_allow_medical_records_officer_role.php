<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_valid');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_valid CHECK (role IN ('admin', 'pendaftaran', 'perawat', 'dokter', 'dokter_gigi', 'farmasi', 'kepala_klinik', 'perekam_medis'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->where('role', 'perekam_medis')->exists()) {
            throw new RuntimeException('Reassign or deactivate PMIK accounts before rolling back the role constraint.');
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_valid');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_valid CHECK (role IN ('admin', 'pendaftaran', 'perawat', 'dokter', 'dokter_gigi', 'farmasi', 'kepala_klinik'))");
    }
};
