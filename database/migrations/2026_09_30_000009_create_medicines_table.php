<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('kode_obat', 50)->unique()->index();
            $table->string('nama_obat')->index();
            $table->string('kategori')->default('Generik'); // Generik, Antibiotik, Analgesik, Mukolitik, Antihipertensi, dll.
            $table->string('bentuk_sediaan')->default('Tablet'); // Tablet, Kapsul, Sirup, Salep, Tetes Mata
            $table->string('satuan', 20)->default('Tab');
            $table->integer('stok')->default(0);
            $table->integer('stok_minimum')->default(50);
            $table->string('nomor_batch')->nullable();
            $table->date('expired_date')->nullable();
            $table->decimal('harga_satuan', 12, 2)->default(0);
            $table->string('aturan_pakai_default')->nullable();
            $table->string('kontraindikasi_alergi')->nullable(); // e.g. "Penisilin", "Sulfa"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
