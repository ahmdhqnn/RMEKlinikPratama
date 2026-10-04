<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_review_sampling_runs', function (Blueprint $table): void {
            $table->id();
            $table->date('period_start')->unique();
            $table->date('period_end');
            $table->unsignedSmallInteger('requested_sample_size');
            $table->unsignedSmallInteger('selected_sample_size');
            $table->string('selection_seed', 64);
            $table->string('sampling_method', 60)->default('stratified_risk_and_routine');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('created_by_name', 255);
            $table->string('created_by_role', 50);
            $table->timestampTz('created_at');
            $table->index(['period_start', 'created_at']);
        });

        DB::statement('ALTER TABLE quality_review_sampling_runs ADD CONSTRAINT quality_review_sampling_sizes_valid CHECK (requested_sample_size BETWEEN 1 AND 100 AND selected_sample_size BETWEEN 1 AND requested_sample_size)');
        DB::statement("ALTER TABLE quality_review_sampling_runs ADD CONSTRAINT quality_review_sampling_method_valid CHECK (sampling_method = 'stratified_risk_and_routine')");
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_quality_review_sampling_run_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Quality review sampling runs are append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER quality_review_sampling_runs_append_only
            BEFORE UPDATE OR DELETE ON quality_review_sampling_runs
            FOR EACH ROW EXECUTE FUNCTION prevent_quality_review_sampling_run_mutation();
        SQL);
    }

    public function down(): void
    {
        if (DB::table('quality_review_sampling_runs')->exists()) {
            throw new RuntimeException('Archive quality review sampling cohorts before rolling back this migration.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS quality_review_sampling_runs_append_only ON quality_review_sampling_runs');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_quality_review_sampling_run_mutation()');
        DB::statement('ALTER TABLE quality_review_sampling_runs DROP CONSTRAINT IF EXISTS quality_review_sampling_sizes_valid');
        DB::statement('ALTER TABLE quality_review_sampling_runs DROP CONSTRAINT IF EXISTS quality_review_sampling_method_valid');
        Schema::dropIfExists('quality_review_sampling_runs');
    }
};
