<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE medical_records ALTER COLUMN status SET DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE medical_records ALTER COLUMN status SET DEFAULT 'final'");
    }
};
