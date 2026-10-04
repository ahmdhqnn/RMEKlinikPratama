<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->string('identity_status', 20)->default('provisional')->index();
            $table->timestamp('identity_verified_at')->nullable();
            $table->string('identity_verification_method', 40)->nullable();
        });

        DB::statement('ALTER TABLE patients ALTER COLUMN jenis_kelamin DROP NOT NULL');

        Schema::table('queues', function (Blueprint $table): void {
            $table->boolean('is_emergency')->default(false)->index();
        });

        DB::statement("ALTER TABLE patients ADD CONSTRAINT patients_identity_status_valid CHECK (identity_status IN ('verified', 'provisional', 'unidentified'))");
        DB::statement("ALTER TABLE patients ADD CONSTRAINT patients_identity_verification_consistent CHECK ((identity_status = 'verified' AND identity_verified_at IS NOT NULL AND identity_verification_method IS NOT NULL) OR (identity_status != 'verified' AND identity_verified_at IS NULL AND identity_verification_method IS NULL))");
        DB::statement("ALTER TABLE patients ADD CONSTRAINT patients_identity_verification_method_valid CHECK (identity_verification_method IS NULL OR identity_verification_method IN ('identity_document', 'employee_card', 'other_official_document'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            DB::table('patients')->where('identity_status', '!=', 'provisional')->exists()
            || DB::table('patients')->whereNotNull('identity_verified_at')->exists()
            || DB::table('patients')->whereNotNull('identity_verification_method')->exists()
            || DB::table('patients')->whereNull('jenis_kelamin')->exists()
            || DB::table('queues')->where('is_emergency', true)->exists()
        ) {
            throw new RuntimeException('Reconcile emergency identity and missing sex data before rolling back this migration.');
        }

        DB::statement('ALTER TABLE patients DROP CONSTRAINT patients_identity_status_valid');
        DB::statement('ALTER TABLE patients DROP CONSTRAINT patients_identity_verification_consistent');
        DB::statement('ALTER TABLE patients DROP CONSTRAINT patients_identity_verification_method_valid');

        Schema::table('queues', function (Blueprint $table): void {
            $table->dropColumn('is_emergency');
        });

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropIndex(['identity_status']);
            $table->dropColumn(['identity_status', 'identity_verified_at', 'identity_verification_method']);
        });

        DB::statement('ALTER TABLE patients ALTER COLUMN jenis_kelamin SET NOT NULL');
    }
};
