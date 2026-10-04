<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('queue_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_name', 255);
            $table->string('changed_by_role', 50);
            $table->string('reason', 500);
            $table->string('origin', 24);
            $table->timestampTz('recorded_at');
            $table->index(['queue_id', 'recorded_at']);
        });

        DB::statement("ALTER TABLE queue_status_histories ADD CONSTRAINT queue_status_history_status_valid CHECK (to_status IN ('menunggu_triage', 'siap_periksa', 'sedang_diperiksa', 'menunggu_obat', 'sedang_dispensing', 'siap_diambil', 'selesai', 'dibatalkan') AND (from_status IS NULL OR from_status IN ('menunggu_triage', 'siap_periksa', 'sedang_diperiksa', 'menunggu_obat', 'sedang_dispensing', 'siap_diambil', 'selesai', 'dibatalkan')))");
        DB::statement("ALTER TABLE queue_status_histories ADD CONSTRAINT queue_status_history_origin_valid CHECK ((origin = 'registration' AND from_status IS NULL AND to_status = 'menunggu_triage') OR (origin = 'workflow' AND from_status IS NOT NULL AND from_status <> to_status) OR (origin = 'migration_snapshot' AND from_status IS NULL))");
        DB::statement("ALTER TABLE queue_status_histories ADD CONSTRAINT queue_status_transition_valid CHECK (origin <> 'workflow' OR (from_status = 'menunggu_triage' AND to_status = 'siap_periksa') OR (from_status = 'siap_periksa' AND to_status = 'sedang_diperiksa') OR (from_status = 'sedang_diperiksa' AND to_status IN ('menunggu_obat', 'selesai')) OR (from_status = 'menunggu_obat' AND to_status = 'sedang_dispensing') OR (from_status = 'sedang_dispensing' AND to_status = 'siap_diambil') OR (from_status = 'siap_diambil' AND to_status = 'selesai'))");
        DB::statement("CREATE UNIQUE INDEX queue_status_history_one_initial ON queue_status_histories (queue_id) WHERE origin IN ('registration', 'migration_snapshot')");

        $snapshotRecordedAt = now();

        DB::table('queues')->select(['id', 'status'])->orderBy('id')->chunkById(500, function (Collection $queues) use ($snapshotRecordedAt): void {
            $histories = [];

            foreach ($queues as $queue) {
                $histories[] = [
                    'queue_id' => $queue->id,
                    'from_status' => null,
                    'to_status' => $queue->status,
                    'changed_by_user_id' => null,
                    'changed_by_name' => 'System migration',
                    'changed_by_role' => 'system',
                    'reason' => 'Snapshot saat aktivasi riwayat status; jejak transisi sebelumnya tidak tersedia.',
                    'origin' => 'migration_snapshot',
                    'recorded_at' => $snapshotRecordedAt,
                ];
            }

            if ($histories !== []) {
                DB::table('queue_status_histories')->insert($histories);
            }
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_queue_status_history_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Queue status histories are append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER queue_status_histories_append_only
            BEFORE UPDATE OR DELETE ON queue_status_histories
            FOR EACH ROW EXECUTE FUNCTION prevent_queue_status_history_mutation();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('queue_status_histories')->exists()) {
            throw new RuntimeException('Archive queue status history before rolling back this migration.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS queue_status_histories_append_only ON queue_status_histories');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_queue_status_history_mutation()');
        DB::statement('DROP INDEX IF EXISTS queue_status_history_one_initial');
        DB::statement('ALTER TABLE queue_status_histories DROP CONSTRAINT IF EXISTS queue_status_history_origin_valid');
        DB::statement('ALTER TABLE queue_status_histories DROP CONSTRAINT IF EXISTS queue_status_transition_valid');
        DB::statement('ALTER TABLE queue_status_histories DROP CONSTRAINT IF EXISTS queue_status_history_status_valid');
        Schema::dropIfExists('queue_status_histories');
    }
};
