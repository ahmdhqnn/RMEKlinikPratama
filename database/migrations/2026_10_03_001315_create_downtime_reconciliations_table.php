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
        Schema::create('downtime_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('entered_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('paper_reference', 120)->unique();
            $table->timestampTz('service_occurred_at');
            $table->string('status', 24)->default('pending_review');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'service_occurred_at'], 'downtime_reconciliation_queue_idx');
            $table->index('medical_record_id');
        });

        DB::statement("ALTER TABLE downtime_reconciliations ADD CONSTRAINT downtime_reconciliation_status_valid CHECK (status IN ('pending_review', 'reconciled', 'discrepancy'))");
        DB::statement("ALTER TABLE downtime_reconciliations ADD CONSTRAINT downtime_reconciliation_review_state_valid CHECK ((status = 'pending_review' AND reviewed_by_user_id IS NULL AND reviewed_at IS NULL AND review_note IS NULL) OR (status IN ('reconciled', 'discrepancy') AND reviewed_by_user_id IS NOT NULL AND reviewed_at IS NOT NULL AND length(trim(review_note)) > 0))");

        Schema::create('downtime_reconciliation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('downtime_reconciliation_id')->constrained()->restrictOnDelete();
            $table->string('event_type', 24);
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->text('event_note');
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_name', 255);
            $table->string('actor_role', 50);
            $table->timestampTz('recorded_at');
            $table->index(['downtime_reconciliation_id', 'recorded_at'], 'downtime_reconciliation_events_timeline_idx');
        });

        DB::statement("ALTER TABLE downtime_reconciliation_events ADD CONSTRAINT downtime_reconciliation_event_type_valid CHECK (event_type IN ('created', 'reviewed'))");
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_downtime_reconciliation_event_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Downtime reconciliation events are append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER downtime_reconciliation_events_append_only
            BEFORE UPDATE OR DELETE ON downtime_reconciliation_events
            FOR EACH ROW EXECUTE FUNCTION prevent_downtime_reconciliation_event_mutation();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('downtime_reconciliation_events')->exists() || DB::table('downtime_reconciliations')->exists()) {
            throw new RuntimeException('Archive downtime reconciliation records before rolling back this migration.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS downtime_reconciliation_events_append_only ON downtime_reconciliation_events');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_downtime_reconciliation_event_mutation()');
        DB::statement('ALTER TABLE downtime_reconciliation_events DROP CONSTRAINT IF EXISTS downtime_reconciliation_event_type_valid');
        DB::statement('ALTER TABLE downtime_reconciliations DROP CONSTRAINT IF EXISTS downtime_reconciliation_status_valid');
        DB::statement('ALTER TABLE downtime_reconciliations DROP CONSTRAINT IF EXISTS downtime_reconciliation_review_state_valid');
        Schema::dropIfExists('downtime_reconciliation_events');
        Schema::dropIfExists('downtime_reconciliations');
    }
};
