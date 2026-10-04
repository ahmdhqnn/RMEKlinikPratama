<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('queues')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('nurse_id')->constrained('users')->onDelete('cascade');
            $table->integer('sistolik')->nullable(); // mmHg, misal 135
            $table->integer('diastolik')->nullable(); // mmHg, misal 85
            $table->decimal('suhu', 4, 1)->nullable(); // °C, misal 37.8
            $table->integer('nadi')->nullable(); // bpm, misal 88
            $table->integer('laju_nafas')->nullable(); // x/menit, misal 18
            $table->integer('spo2')->nullable(); // %, misal 98
            $table->decimal('berat_badan', 5, 1)->nullable(); // kg, misal 72.0
            $table->decimal('tinggi_badan', 5, 1)->nullable(); // cm, misal 171.0
            $table->decimal('imt', 4, 1)->nullable(); // kg/m²
            $table->string('kesadaran')->default('Compos Mentis');
            $table->integer('skala_nyeri')->default(0); // 0-10
            $table->text('catatan_keperawatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
