<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignMerchCategory;
use App\Models\Uddesign\UddesignMerchType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * merch_types.merch_category_id points to merch_categories.merch_category_id.
 */
class UddesignMerchTypeFactory extends Factory
{
    protected $model = UddesignMerchType::class;

    public function definition(): array
    {
        return [
            'merch_type_id' => $this->faker->unique()->numberBetween(1, 9999),
            'merch_category_id' => fn () => UddesignMerchCategory::factory()->create()->merch_category_id,
            'name' => $this->faker->randomElement(['T-Shirt', 'Mug', 'Tote Bag', 'Keychain']),
        ];
    }
}
