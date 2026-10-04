<?php

namespace Database\Factories;

use App\Models\DowntimeReconciliation;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<DowntimeReconciliation>
 */
class DowntimeReconciliationFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(static function (DowntimeReconciliation $reconciliation): void {
            $actor = User::query()->findOrFail($reconciliation->entered_by_user_id);
            DB::table('downtime_reconciliation_events')->insert([
                'downtime_reconciliation_id' => $reconciliation->id,
                'event_type' => 'created',
                'from_status' => null,
                'to_status' => 'pending_review',
                'event_note' => 'Metadata rekonsiliasi downtime dicatat untuk fixture pengembangan.',
                'actor_user_id' => $actor->id,
                'actor_name' => $actor->name,
                'actor_role' => $actor->role,
                'recorded_at' => now(),
            ]);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'entered_by_user_id' => static fn (array $attributes): int => (int) MedicalRecord::query()
                ->findOrFail($attributes['medical_record_id'])
                ->doctor_id,
            'paper_reference' => 'DT-'.fake()->unique()->bothify('########'),
            'service_occurred_at' => now()->subHour(),
            'status' => 'pending_review',
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }
}
