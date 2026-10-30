<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignMerchCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class UddesignMerchCategoryFactory extends Factory
{
    protected $model = UddesignMerchCategory::class;

    public function definition(): array
    {
        return [
            'merch_category_id' => $this->faker->unique()->numberBetween(1, 9999),
            'name' => $this->faker->randomElement(['Apparel', 'Drinkware', 'Accessories']),
        ];
    }
}
