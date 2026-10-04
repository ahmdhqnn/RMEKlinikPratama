<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('dokter')->after('email'); // admin, pendaftaran, perawat, dokter, farmasi, kepala_klinik
            $table->string('nip')->nullable()->after('role');
            $table->string('sip')->nullable()->after('nip');
            $table->string('specialty')->nullable()->after('sip'); // Poli Umum, Poli Gigi, Farmasi, Front Office, Triage Keperawatan, Manajemen
            $table->string('avatar_url')->nullable()->after('specialty');
            $table->string('phone')->nullable()->after('avatar_url');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'nip', 'sip', 'specialty', 'avatar_url', 'phone', 'is_active']);
        });
    }
};
