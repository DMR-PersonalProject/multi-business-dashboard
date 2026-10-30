<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignPrintCategory;
use App\Models\Uddesign\UddesignPrintType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * print_types.print_category_id points to print_categories.print_category_id.
 */
class UddesignPrintTypeFactory extends Factory
{
    protected $model = UddesignPrintType::class;

    public function definition(): array
    {
        return [
            'print_type_id' => $this->faker->unique()->numberBetween(1, 9999),
            'print_category_id' => fn () => UddesignPrintCategory::factory()->create()->print_category_id,
            'name' => $this->faker->randomElement(['Tarpaulin 3x6 ft', 'Vinyl Sticker Sheet', 'Business Cards (100 pcs)', 'B&W Print (A4)']),
        ];
    }
}
