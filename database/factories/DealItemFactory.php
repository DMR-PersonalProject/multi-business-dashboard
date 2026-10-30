<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\DealItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealItemFactory extends Factory
{
    protected $model = DealItem::class;

    public function definition()
    {
        return [
            'deal_id' => Deal::factory(),
            'description' => $this->faker->randomElement(['Custom printed T-shirt', 'Tarpaulin 3x6 ft', 'Sublimated mug', 'Die-cut stickers (sheet)', 'Embroidered cap']),
            'quantity' => $this->faker->numberBetween(5, 100),
            'unit_price' => $this->faker->randomElement([45, 80, 150, 220, 350, 450]),
            'total_price' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['unit_price'];
            },
        ];
    }
}
