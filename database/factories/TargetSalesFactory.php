<?php

namespace Database\Factories;

use App\Models\TargetSales;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * One calendar month for one business.
 */
class TargetSalesFactory extends Factory
{
    protected $model = TargetSales::class;

    public function definition()
    {
        $month = now()->subMonths($this->faker->numberBetween(0, 11))->startOfMonth();

        return [
            'business_type' => $this->faker->randomElement(['UdDesign', 'Kuwago1', 'Kuwago2']),
            'amount' => $this->faker->numberBetween(300000, 600000),
            'start_date' => $month->toDateString(),
            'end_date' => $month->copy()->endOfMonth()->toDateString(),
        ];
    }
}
