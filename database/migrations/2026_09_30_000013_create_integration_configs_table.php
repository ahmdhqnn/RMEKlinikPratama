<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_configs', function (Blueprint $table) {
            $table->id();
            $table->string('system_code', 50)->unique(); // satusehat, bpjs_pcare
            $table->string('system_name');
            $table->string('organization_id')->nullable();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('base_url')->nullable();
            $table->string('auth_url')->nullable();
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('connected');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_configs');
    }
};
