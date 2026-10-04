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
        Schema::table('quality_review_sampling_runs', function (Blueprint $table): void {
            $table->jsonb('population_counts_json')->default('{}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('quality_review_sampling_runs')->whereRaw("population_counts_json <> '{}'::jsonb")->exists()) {
            throw new RuntimeException('Preserve sampling population snapshots before rolling back this migration.');
        }

        Schema::table('quality_review_sampling_runs', function (Blueprint $table): void {
            $table->dropColumn('population_counts_json');
        });
    }
};
