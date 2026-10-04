<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('no_rm')->unique()->index();
            $table->text('nik')->nullable(); // encrypted
            $table->string('nip', 30)->nullable()->index();
            $table->string('nama_lengkap')->index();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('golongan_darah', 10)->nullable();
            $table->string('no_bpjs', 30)->nullable()->index();
            $table->string('jenis_pasien')->default('Umum');
            $table->string('unit_kerja')->nullable();
            $table->string('no_hp', 25)->nullable();
            $table->text('alamat')->nullable();
            $table->text('riwayat_alergi')->nullable(); // e.g. Amoxicillin & Golongan Penisilin
            $table->text('alergi_keterangan')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
