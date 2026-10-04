<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('kategori', [
                'lab_luar',
                'rontgen_radiologi',
                'jawaban_rujukan',
                'resume_medis_luar',
            ])->default('lab_luar')->index();
            $table->string('judul_dokumen');
            $table->string('instansi_penerbit')->nullable(); // Misal: RSUP Cipto Mangunkusumo, Lab Prodia, dsb.
            $table->date('tanggal_dokumen')->nullable();
            $table->string('file_path');
            $table->string('file_type', 20); // pdf, jpg, png, dcm
            $table->integer('file_size_kb');
            $table->text('catatan_interpretasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_attachments');
    }
};
