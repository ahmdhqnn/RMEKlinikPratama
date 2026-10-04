<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('queues')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->text('subjective')->nullable(); // Anamnesis dokter & keluhan
            $table->text('objective')->nullable(); // Pemeriksaan fisik dokter
            $table->text('assessment')->nullable(); // Diagnosa klinis
            $table->text('plan')->nullable(); // Rencana terapi & edukasi
            $table->string('icd10_primary_code', 20)->nullable()->index();
            $table->string('icd10_primary_name')->nullable();
            $table->json('icd10_secondary_json')->nullable();
            $table->text('tindakan_medis')->nullable(); // Tindakan / Prosedur ICD-9-CM
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->string('satusehat_encounter_id')->nullable();
            $table->enum('satusehat_sync_status', ['not_configured', 'pending', 'synced', 'failed'])->default('not_configured');
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
