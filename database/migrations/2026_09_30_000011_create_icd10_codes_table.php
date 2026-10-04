<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icd10_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->index();
            $table->string('name_en');
            $table->string('name_id');
            $table->string('category')->nullable();
            $table->boolean('is_common')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('icd9_procedures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->index();
            $table->string('name_en');
            $table->string('name_id');
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd9_procedures');
        Schema::dropIfExists('icd10_codes');
    }
};
