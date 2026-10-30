<?php

namespace Database\Factories\Restaurant;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared definition for expense types.
 * expense_types.expense_category_id points to expense_categories.expense_category_id.
 */
abstract class ExpenseTypeFactory extends Factory
{
    /** The expense category model of the same business. */
    protected string $categoryModel;

    public function definition(): array
    {
        return [
            'expense_type_id' => $this->faker->unique()->numberBetween(1, 9999),
            'expense_category_id' => fn () => $this->categoryModel::factory()->create()->expense_category_id,
            'name' => $this->faker->randomElement(['Electricity', 'Water', 'Packaging', 'Cleaning Supplies', 'Equipment Repair']),
        ];
    }
}
