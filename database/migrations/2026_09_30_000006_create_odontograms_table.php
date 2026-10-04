<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('dentist_id')->constrained('users')->onDelete('cascade');
            $table->json('teeth_data_json'); // Array 52 gigi dengan kondisi per elemen / permukaan
            $table->string('oklusi')->default('Normal Bite');
            $table->string('torus_palatinus')->default('Tidak Ada');
            $table->string('torus_mandibularis')->default('Tidak Ada');
            $table->string('palatum')->default('Sedang');
            $table->string('diastema')->default('Tidak Ada');
            $table->text('anomali_gigi')->nullable();
            $table->text('catatan_khusus')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontograms');
    }
};
