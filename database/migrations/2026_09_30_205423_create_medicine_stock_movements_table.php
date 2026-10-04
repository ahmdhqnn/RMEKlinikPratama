<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type', 20);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('reason', 1000);
            $table->string('reference', 100)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['medicine_id', 'occurred_at']);
        });

        DB::statement("ALTER TABLE medicine_stock_movements ADD CONSTRAINT medicine_stock_movements_type_valid CHECK (movement_type IN ('opening', 'receipt', 'dispense'))");
        DB::statement("ALTER TABLE medicine_stock_movements ADD CONSTRAINT medicine_stock_movements_balance_valid CHECK (quantity > 0 AND ((movement_type = 'dispense' AND stock_after = stock_before - quantity) OR (movement_type IN ('opening', 'receipt') AND stock_after = stock_before + quantity)))");
        DB::statement("INSERT INTO medicine_stock_movements (medicine_id, movement_type, quantity, stock_before, stock_after, reason, occurred_at, created_at, updated_at) SELECT id, 'opening', stok, 0, stok, 'Saldo awal saat ledger inventori diaktifkan.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP FROM medicines WHERE stok > 0");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_medicine_stock_movement_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Medicine stock movements are append-only';
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS medicine_stock_movements_append_only ON medicine_stock_movements;
            CREATE TRIGGER medicine_stock_movements_append_only
            BEFORE UPDATE OR DELETE ON medicine_stock_movements
            FOR EACH ROW EXECUTE FUNCTION prevent_medicine_stock_movement_mutation();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_stock_movements');
        DB::unprepared('DROP FUNCTION IF EXISTS prevent_medicine_stock_movement_mutation()');
    }
};
