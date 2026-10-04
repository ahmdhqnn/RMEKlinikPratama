<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('medical_record_quality_reviews')->whereNull('reviewed_by_user_id')->exists()) {
            throw new RuntimeException('Assign the historical reviewer before requiring a reviewer user for every quality review.');
        }

        DB::statement('ALTER TABLE medical_record_quality_reviews DROP CONSTRAINT IF EXISTS medical_record_quality_reviews_reviewed_by_user_id_foreign');
        DB::statement('ALTER TABLE medical_record_quality_reviews ALTER COLUMN reviewed_by_user_id SET NOT NULL');
        DB::statement('ALTER TABLE medical_record_quality_reviews ADD CONSTRAINT medical_record_quality_reviews_reviewed_by_user_id_foreign FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE RESTRICT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE medical_record_quality_reviews DROP CONSTRAINT IF EXISTS medical_record_quality_reviews_reviewed_by_user_id_foreign');
        DB::statement('ALTER TABLE medical_record_quality_reviews ALTER COLUMN reviewed_by_user_id DROP NOT NULL');
        DB::statement('ALTER TABLE medical_record_quality_reviews ADD CONSTRAINT medical_record_quality_reviews_reviewed_by_user_id_foreign FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL');
    }
};
