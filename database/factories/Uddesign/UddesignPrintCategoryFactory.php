<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignPrintCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class UddesignPrintCategoryFactory extends Factory
{
    protected $model = UddesignPrintCategory::class;

    public function definition(): array
    {
        return [
            'print_category_id' => $this->faker->unique()->numberBetween(1, 9999),
            'name' => $this->faker->randomElement(['Tarpaulin', 'Stickers', 'Business Cards', 'Document Printing']),
        ];
    }
}
