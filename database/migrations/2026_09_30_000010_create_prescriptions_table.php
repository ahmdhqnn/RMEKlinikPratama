<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_resep', 30)->unique()->index();
            $table->foreignId('queue_id')->constrained('queues')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pharmacist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'pending',      // Resep Masuk
                'processing',   // Sedang Disiapkan / Dispensing
                'ready',        // Siap Diambil
                'dispensed',    // Selesai Diserahkan
                'cancelled',
            ])->default('pending')->index();
            $table->text('catatan_dokter_farmasi')->nullable();
            $table->text('catatan_apoteker')->nullable();
            $table->timestamp('waktu_verifikasi')->nullable();
            $table->timestamp('waktu_penyerahan')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->integer('jumlah');
            $table->string('aturan_pakai'); // e.g. "3 x 1 tablet sesudah makan"
            $table->text('catatan_khusus')->nullable(); // e.g. "prn demam > 38°C"
            $table->boolean('is_dispensed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
