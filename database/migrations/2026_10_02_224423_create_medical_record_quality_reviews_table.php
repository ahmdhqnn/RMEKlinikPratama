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
        Schema::create('medical_record_quality_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('review_round');
            $table->unsignedInteger('reviewed_record_version');
            $table->string('review_result', 24);
            $table->json('checklist_json');
            $table->text('findings')->nullable();
            $table->foreignId('reviewed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('reviewed_by_name', 255);
            $table->string('reviewed_by_role', 50);
            $table->timestampTz('reviewed_at');
            $table->unique(['medical_record_id', 'review_round'], 'medical_record_quality_reviews_round_unique');
            $table->index(['review_result', 'reviewed_at']);
        });

        DB::statement("ALTER TABLE medical_record_quality_reviews ADD CONSTRAINT medical_record_quality_review_result_valid CHECK (review_result IN ('complete', 'needs_follow_up'))");
        DB::statement("ALTER TABLE medical_record_quality_reviews ADD CONSTRAINT medical_record_quality_review_findings_required CHECK (review_result <> 'needs_follow_up' OR length(trim(findings)) > 0)");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_medical_record_quality_review_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Medical record quality reviews are append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER medical_record_quality_reviews_append_only
            BEFORE UPDATE OR DELETE ON medical_record_quality_reviews
            FOR EACH ROW EXECUTE FUNCTION prevent_medical_record_quality_review_mutation();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('medical_record_quality_reviews')->exists()) {
            throw new RuntimeException('Archive medical record quality reviews before rolling back this migration.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS medical_record_quality_reviews_append_only ON medical_record_quality_reviews');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_medical_record_quality_review_mutation()');
        DB::statement('ALTER TABLE medical_record_quality_reviews DROP CONSTRAINT IF EXISTS medical_record_quality_review_result_valid');
        DB::statement('ALTER TABLE medical_record_quality_reviews DROP CONSTRAINT IF EXISTS medical_record_quality_review_findings_required');
        Schema::dropIfExists('medical_record_quality_reviews');
    }
};
