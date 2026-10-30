<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignPrintDetail;
use App\Models\Uddesign\UddesignPrintType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * print_details.print_type_id points to print_types.print_type_id.
 */
class UddesignPrintDetailFactory extends Factory
{
    protected $model = UddesignPrintDetail::class;

    public function definition(): array
    {
        return [
            'print_type_id' => fn () => UddesignPrintType::factory()->create()->print_type_id,
            'pcs' => $this->faker->numberBetween(1, 50),
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
        ];
    }
}
