<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('action', 50)->index(); // LOGIN, VIEW_PATIENT, CREATE_QUEUE, INPUT_TTV, CREATE_SOAP, DISPENSE_MEDICINE, ISSUE_LETTER
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('description');
            $table->json('payload_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            // Note: Immutable log has no updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
