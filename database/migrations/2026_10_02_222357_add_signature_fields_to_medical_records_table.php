<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table): void {
            $table->unsignedBigInteger('signed_by_user_id')->nullable()->after('doctor_id');
            $table->string('signed_by_name', 255)->nullable()->after('signed_by_user_id');
            $table->string('signed_by_role', 50)->nullable()->after('signed_by_name');
            $table->timestampTz('signed_at')->nullable()->after('signed_by_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table): void {
            $table->dropColumn(['signed_by_user_id', 'signed_by_name', 'signed_by_role', 'signed_at']);
        });
    }
};
