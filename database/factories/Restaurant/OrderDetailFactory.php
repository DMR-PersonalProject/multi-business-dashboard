<?php

namespace Database\Factories\Restaurant;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared definition for the Kuwago One and Kuwago Two order lines.
 * order_details.dish_id points to dishes.id.
 */
abstract class OrderDetailFactory extends Factory
{
    /** The dish model of the same restaurant. */
    protected string $dishModel;

    public function definition(): array
    {
        return [
            'dish_id' => $this->dishModel::factory(),
            'pcs' => $this->faker->numberBetween(1, 12),
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
        ];
    }
}
