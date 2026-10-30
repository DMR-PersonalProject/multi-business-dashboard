<?php

namespace Database\Factories\Restaurant;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared definition for expense categories (Kuwago One, Kuwago Two and UDDesign).
 */
abstract class ExpenseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'expense_category_id' => $this->faker->unique()->numberBetween(1, 9999),
            'name' => $this->faker->randomElement(['Ingredients', 'Utilities', 'Salaries', 'Supplies', 'Rent', 'Maintenance']),
        ];
    }
}
