<?php

namespace Database\Factories\Restaurant;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared definition for the Kuwago One and Kuwago Two dishes.
 * dishes.category_id points to categories.category_id, not categories.id.
 */
abstract class MenuDishFactory extends Factory
{
    /** The category model of the same restaurant. */
    protected string $categoryModel;

    public function definition(): array
    {
        return [
            'dish_id' => $this->faker->unique()->numberBetween(1, 9999),
            'category_id' => fn () => $this->categoryModel::factory()->create()->category_id,
            'name' => $this->faker->randomElement(['Chicken Adobo Bowl', 'Beef Tapa', 'Carbonara', 'Iced Latte', 'Mango Float', 'Clubhouse Sandwich']),
        ];
    }
}
