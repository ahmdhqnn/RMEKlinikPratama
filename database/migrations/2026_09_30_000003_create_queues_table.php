<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_antrean', 20)->index();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->enum('poli', ['umum', 'gigi'])->default('umum')->index();
            $table->foreignId('dokter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_antrean')->index();
            $table->time('waktu_daftar')->nullable();
            $table->text('keluhan_singkat')->nullable();
            $table->enum('status', [
                'menunggu_triage',
                'siap_periksa',
                'sedang_diperiksa',
                'menunggu_obat',
                'sedang_dispensing',
                'siap_diambil',
                'selesai',
                'dibatalkan',
            ])->default('menunggu_triage')->index();
            $table->enum('prioritas', ['normal', 'urgent', 'lansia'])->default('normal');
            $table->foreignId('triage_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('examined_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waktu_panggil')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->timestamps();

            $table->index(['tanggal_antrean', 'status', 'poli']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
