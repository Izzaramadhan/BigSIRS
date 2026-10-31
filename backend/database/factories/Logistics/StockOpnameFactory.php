<?php

namespace Database\Factories\Logistics;

use App\Models\Logistics\StockOpname;
use App\Models\MasterData\Warehouse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpname>
 */
class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        $date = Carbon::now();

        return [
            'opname_number' => 'SO-'.$date->format('Ym').'-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'warehouse_id' => Warehouse::factory(),
            'title' => 'Audit Rutin Persediaan Oral',
            'type' => 'Audit Rutin',
            'status' => StockOpname::STATUS_DRAFT,
            'start_date' => $date,
            'location_detail' => 'Rak A1 - Rak C4',
            'officer_name' => 'Apt. Budi Santoso, S.Farm',
            'officer_role' => 'Staf Farmasi',
            'notes' => $this->faker->sentence(),
            'total_items' => 0,
            'counted_items' => 0,
            'difference_count' => 0,
            'total_difference_value' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function counting(): static
    {
        return $this->state(fn () => [
            'status' => StockOpname::STATUS_COUNTING,
            'start_date' => Carbon::now(),
        ]);
    }

    public function review(): static
    {
        return $this->state(fn () => [
            'status' => StockOpname::STATUS_REVIEW,
            'submitted_by' => User::factory(),
            'submitted_at' => Carbon::now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => StockOpname::STATUS_COMPLETED,
            'approved_by' => User::factory(),
            'approved_at' => Carbon::now(),
            'end_date' => Carbon::now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => StockOpname::STATUS_CANCELLED,
            'cancelled_by' => User::factory(),
            'cancelled_at' => Carbon::now(),
            'cancel_reason' => 'Dibatalkan oleh petugas logistik.',
            'end_date' => Carbon::now(),
        ]);
    }
}
