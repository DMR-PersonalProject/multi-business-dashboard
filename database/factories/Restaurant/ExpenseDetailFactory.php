<?php

namespace Database\Factories\Restaurant;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared definition for expense entries.
 * expense_details.expense_type_id points to expense_types.expense_type_id.
 */
abstract class ExpenseDetailFactory extends Factory
{
    /** The expense type model of the same business. */
    protected string $typeModel;

    public function definition(): array
    {
        return [
            'expense_type_id' => fn () => $this->typeModel::factory()->create()->expense_type_id,
            'price' => $this->faker->numberBetween(200, 8000),
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
        ];
    }
}
