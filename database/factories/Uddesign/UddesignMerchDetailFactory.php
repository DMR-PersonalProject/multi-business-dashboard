<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignMerchDetail;
use App\Models\Uddesign\UddesignMerchType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * merch_details.merch_type_id points to merch_types.merch_type_id.
 */
class UddesignMerchDetailFactory extends Factory
{
    protected $model = UddesignMerchDetail::class;

    public function definition(): array
    {
        return [
            'merch_type_id' => fn () => UddesignMerchType::factory()->create()->merch_type_id,
            'pcs' => $this->faker->numberBetween(1, 50),
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
        ];
    }
}
