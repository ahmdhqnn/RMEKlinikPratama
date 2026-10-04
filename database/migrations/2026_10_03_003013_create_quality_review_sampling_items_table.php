<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_review_sampling_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sampling_run_id')->constrained('quality_review_sampling_runs')->restrictOnDelete();
            $table->foreignId('medical_record_id')->constrained()->restrictOnDelete();
            $table->string('poli', 20);
            $table->string('selection_reason', 24);
            $table->timestampTz('selected_at');
            $table->unique(['sampling_run_id', 'medical_record_id'], 'quality_review_sampling_item_unique');
            $table->index(['sampling_run_id', 'poli', 'selection_reason'], 'quality_review_sampling_strata_idx');
        });

        DB::statement("ALTER TABLE quality_review_sampling_items ADD CONSTRAINT quality_review_sampling_poli_valid CHECK (poli IN ('umum', 'gigi'))");
        DB::statement("ALTER TABLE quality_review_sampling_items ADD CONSTRAINT quality_review_sampling_reason_valid CHECK (selection_reason IN ('high_risk', 'routine'))");
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_quality_review_sampling_item_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Quality review sampling items are append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER quality_review_sampling_items_append_only
            BEFORE UPDATE OR DELETE ON quality_review_sampling_items
            FOR EACH ROW EXECUTE FUNCTION prevent_quality_review_sampling_item_mutation();
        SQL);
    }

    public function down(): void
    {
        if (DB::table('quality_review_sampling_items')->exists()) {
            throw new RuntimeException('Archive quality review sampling items before rolling back this migration.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS quality_review_sampling_items_append_only ON quality_review_sampling_items');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_quality_review_sampling_item_mutation()');
        DB::statement('ALTER TABLE quality_review_sampling_items DROP CONSTRAINT IF EXISTS quality_review_sampling_poli_valid');
        DB::statement('ALTER TABLE quality_review_sampling_items DROP CONSTRAINT IF EXISTS quality_review_sampling_reason_valid');
        Schema::dropIfExists('quality_review_sampling_items');
    }
};
