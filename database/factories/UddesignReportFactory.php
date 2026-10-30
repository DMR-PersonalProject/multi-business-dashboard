<?php

namespace Database\Factories;

use App\Models\UddesignReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * total_sales is print + merch + custom sales and equals cash + gcash;
 * total_expenses is print + merch + custom expenses.
 */
class UddesignReportFactory extends Factory
{
    protected $model = UddesignReport::class;

    public function definition(): array
    {
        $print = $this->faker->numberBetween(1500, 6000);
        $merch = $this->faker->numberBetween(800, 5000);
        $custom = $this->faker->randomElement([0, 0, $this->faker->numberBetween(3000, 20000)]);
        $total = $print + $merch + $custom;
        $cash = round($total * $this->faker->randomFloat(2, 0.3, 0.6), 2);

        $printExpenses = round($print * 0.4, 2);
        $merchExpenses = round($merch * 0.5, 2);
        $customExpenses = round($custom * 0.45, 2);

        return [
            'gcash' => $total - $cash,
            'cash' => $cash,
            'print_sales' => $print,
            'merch_sales' => $merch,
            'custom_sales' => $custom,
            'total_sales' => $total,
            'print_expenses' => $printExpenses,
            'merch_expenses' => $merchExpenses,
            'custom_expenses' => $customExpenses,
            'total_expenses' => $printExpenses + $merchExpenses + $customExpenses,
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
        ];
    }
}
