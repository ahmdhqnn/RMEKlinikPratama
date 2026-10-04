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
            $table->string('disposition', 24)->nullable()->after('status');
            $table->date('follow_up_at')->nullable()->after('disposition');
            $table->text('follow_up_instructions')->nullable()->after('follow_up_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table): void {
            $table->dropColumn(['disposition', 'follow_up_at', 'follow_up_instructions']);
        });
    }
};
