<?php

namespace Database\Factories\Restaurant;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared definition for the Kuwago One and Kuwago Two menu categories.
 */
abstract class MenuCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => $this->faker->unique()->numberBetween(1, 9999),
            'name' => $this->faker->randomElement(['Rice Meals', 'Pasta', 'Snacks', 'Coffee', 'Non-Coffee', 'Desserts']),
        ];
    }
}
