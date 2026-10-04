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
        Schema::create('medical_record_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('revision_type', 24);
            $table->text('reason');
            $table->json('snapshot_json');
            $table->text('addendum_text')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id')->nullable();
            $table->string('recorded_by_name', 255);
            $table->string('recorded_by_role', 50);
            $table->string('origin', 24);
            $table->timestampTz('recorded_at');
            $table->unique(['medical_record_id', 'version_number'], 'medical_record_revisions_version_unique');
            $table->index(['medical_record_id', 'recorded_at']);
        });

        DB::statement("ALTER TABLE medical_record_revisions ADD CONSTRAINT medical_record_revision_type_valid CHECK (revision_type IN ('original', 'correction', 'addendum'))");
        DB::statement("ALTER TABLE medical_record_revisions ADD CONSTRAINT medical_record_revision_origin_valid CHECK (origin IN ('application', 'activation_snapshot'))");

        $snapshotRecordedAt = now();
        $snapshotFields = [
            'subjective', 'objective', 'assessment', 'plan', 'icd10_primary_code', 'icd10_primary_name',
            'icd10_secondary_json', 'tindakan_medis', 'status', 'disposition', 'follow_up_at',
            'follow_up_instructions', 'doctor_id', 'signed_by_user_id', 'signed_by_name', 'signed_by_role',
            'signed_at', 'created_at', 'updated_at',
        ];

        DB::table('medical_records')
            ->where('status', 'final')
            ->orderBy('id')
            ->chunkById(500, function (Collection $records) use ($snapshotFields, $snapshotRecordedAt): void {
                $revisions = [];

                foreach ($records as $record) {
                    $snapshot = [];

                    foreach ($snapshotFields as $field) {
                        $snapshot[$field] = $record->{$field};
                    }

                    if (is_string($snapshot['icd10_secondary_json'])) {
                        $snapshot['icd10_secondary_json'] = json_decode($snapshot['icd10_secondary_json'], true);
                    }

                    $revisions[] = [
                        'medical_record_id' => $record->id,
                        'version_number' => 1,
                        'revision_type' => 'original',
                        'reason' => 'Snapshot aktivasi versioning; bukan rekonstruksi riwayat perubahan atau tanda tangan historis.',
                        'snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                        'addendum_text' => null,
                        'recorded_by_user_id' => null,
                        'recorded_by_name' => 'Snapshot aktivasi versioning',
                        'recorded_by_role' => 'system',
                        'origin' => 'activation_snapshot',
                        'recorded_at' => $snapshotRecordedAt,
                    ];
                }

                if ($revisions !== []) {
                    DB::table('medical_record_revisions')->insert($revisions);
                }
            });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_medical_record_revision_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Medical record revisions are append-only';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER medical_record_revisions_append_only
            BEFORE UPDATE OR DELETE ON medical_record_revisions
            FOR EACH ROW EXECUTE FUNCTION prevent_medical_record_revision_mutation();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('medical_record_revisions')->exists()) {
            throw new RuntimeException('Archive medical record revisions before rolling back this migration.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS medical_record_revisions_append_only ON medical_record_revisions');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_medical_record_revision_mutation()');
        DB::statement('ALTER TABLE medical_record_revisions DROP CONSTRAINT IF EXISTS medical_record_revision_type_valid');
        DB::statement('ALTER TABLE medical_record_revisions DROP CONSTRAINT IF EXISTS medical_record_revision_origin_valid');
        Schema::dropIfExists('medical_record_revisions');
    }
};
