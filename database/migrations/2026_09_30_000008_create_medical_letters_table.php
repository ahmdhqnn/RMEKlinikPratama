<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->enum('jenis_surat', [
                'surat_sakit',
                'surat_sehat',
                'surat_rujukan',
                'pengantar_lab',
            ])->index();
            $table->string('nomor_surat')->unique()->index(); // e.g. 440/082/KP-KEMEN/X/2025
            $table->date('tanggal_surat');
            $table->integer('lama_hari')->nullable(); // Untuk surat sakit
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('tujuan_faskes_rujukan')->nullable();
            $table->string('poli_tujuan_rujukan')->nullable();
            $table->text('alasan_rujukan')->nullable();
            $table->string('icd10_code', 20)->nullable();
            $table->text('pemeriksaan_lab_diminta')->nullable();
            $table->text('keperluan')->nullable(); // misal: Keperluan Ujian / Persyaratan Diklat
            $table->string('status_kesehatan')->nullable(); // Sehat Fisik, Bebas Narkoba
            $table->text('catatan_tambahan')->nullable();
            $table->string('qr_token', 64)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_letters');
    }
};
