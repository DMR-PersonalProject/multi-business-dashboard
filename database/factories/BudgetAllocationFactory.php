<?php

namespace Database\Factories;

use App\Models\BudgetAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One calendar month for one business.
 */
class BudgetAllocationFactory extends Factory
{
    protected $model = BudgetAllocation::class;

    public function definition()
    {
        $month = now()->subMonths($this->faker->numberBetween(0, 11))->startOfMonth();

        return [
            'business_type' => $this->faker->randomElement(['UdDesign', 'Kuwago1', 'Kuwago2']),
            'amount' => $this->faker->numberBetween(200000, 450000),
            'start_date' => $month->toDateString(),
            'end_date' => $month->copy()->endOfMonth()->toDateString(),
        ];
    }
}
