<?php

namespace Database\Factories\Restaurant;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared definition for the Kuwago One and Kuwago Two daily reports.
 * sales is always cash + gcash.
 */
abstract class DailyReportFactory extends Factory
{
    public function definition(): array
    {
        $cash = $this->faker->numberBetween(5000, 12000);
        $gcash = $this->faker->numberBetween(2500, 8000);

        return [
            'orders' => $this->faker->numberBetween(40, 120),
            'cash' => $cash,
            'gcash' => $gcash,
            'sales' => $cash + $gcash,
            'expenses' => $this->faker->numberBetween(6000, 14000),
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
        ];
    }
}
