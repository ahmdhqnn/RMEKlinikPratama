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
        Schema::create('medical_record_quality_review_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_record_quality_review_id')->constrained()->restrictOnDelete();
            $table->string('item_key', 80);
            $table->string('item_label', 255);
            $table->text('finding');
            $table->foreignId('assigned_to_user_id')->constrained('users')->restrictOnDelete();
            $table->date('due_at');
            $table->string('status', 24)->default('open');
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('resolved_record_version')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->unique(['medical_record_quality_review_id', 'item_key'], 'quality_review_follow_up_item_unique');
            $table->index(['assigned_to_user_id', 'status', 'due_at'], 'quality_review_follow_up_work_queue_idx');
        });

        DB::statement("ALTER TABLE medical_record_quality_review_follow_ups ADD CONSTRAINT quality_review_follow_up_status_valid CHECK (status IN ('open', 'in_progress', 'resolved', 'not_applicable'))");
        DB::statement("ALTER TABLE medical_record_quality_review_follow_ups ADD CONSTRAINT quality_review_follow_up_resolution_valid CHECK ((status IN ('open', 'in_progress') AND resolution_note IS NULL AND resolved_by_user_id IS NULL AND resolved_at IS NULL AND resolved_record_version IS NULL) OR (status = 'resolved' AND length(trim(resolution_note)) > 0 AND resolved_by_user_id IS NOT NULL AND resolved_at IS NOT NULL AND resolved_record_version IS NOT NULL) OR (status = 'not_applicable' AND length(trim(resolution_note)) > 0 AND resolved_by_user_id IS NOT NULL AND resolved_at IS NOT NULL AND resolved_record_version IS NULL))");

        Schema::create('medical_record_quality_review_follow_up_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_record_quality_review_follow_up_id')->constrained('medical_record_quality_review_follow_ups')->restrictOnDelete();
            $table->string('event_type', 24);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->json('event_data')->nullable();
            $table->text('event_note');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_name', 255);
            $table->string('actor_role', 50);
            $table->timestampTz('recorded_at');
            $table->index(['medical_record_quality_review_follow_up_id', 'recorded_at'], 'quality_review_follow_up_events_timeline_idx');
        });

        DB::statement("ALTER TABLE medical_record_quality_review_follow_up_events ADD CONSTRAINT quality_review_follow_up_event_type_valid CHECK (event_type IN ('created', 'status_changed'))");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_medical_record_quality_review_follow_up_event_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Medical record quality review follow-up events are append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER medical_record_quality_review_follow_up_events_append_only
            BEFORE UPDATE OR DELETE ON medical_record_quality_review_follow_up_events
            FOR EACH ROW EXECUTE FUNCTION prevent_medical_record_quality_review_follow_up_event_mutation();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('medical_record_quality_review_follow_up_events')->exists() || DB::table('medical_record_quality_review_follow_ups')->exists()) {
            throw new RuntimeException('Archive quality review follow-up tasks and events before rolling back this migration.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS medical_record_quality_review_follow_up_events_append_only ON medical_record_quality_review_follow_up_events');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_medical_record_quality_review_follow_up_event_mutation()');
        DB::statement('ALTER TABLE medical_record_quality_review_follow_up_events DROP CONSTRAINT IF EXISTS quality_review_follow_up_event_type_valid');
        DB::statement('ALTER TABLE medical_record_quality_review_follow_ups DROP CONSTRAINT IF EXISTS quality_review_follow_up_status_valid');
        DB::statement('ALTER TABLE medical_record_quality_review_follow_ups DROP CONSTRAINT IF EXISTS quality_review_follow_up_resolution_valid');
        Schema::dropIfExists('medical_record_quality_review_follow_up_events');
        Schema::dropIfExists('medical_record_quality_review_follow_ups');
    }
};
