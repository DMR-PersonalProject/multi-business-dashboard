<?php

namespace Database\Factories;

use App\Models\UddesignFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fake customer feedback; names are "Demo Guest NNN", never real people.
 */
class UddesignFeedbackFactory extends Factory
{
    protected $model = UddesignFeedback::class;

    public function definition()
    {
        $type = $this->faker->randomElement(['Comment', 'Comment', 'Suggestion', 'Complaint']);

        return [
            'name' => sprintf('Demo Guest %03d', $this->faker->numberBetween(1, 999)),
            'feedback_type' => $type,
            'product_name' => $this->faker->randomElement(['T-Shirt Print', 'Tarpaulin 3x6 ft', 'Sublimated Mug', 'Vinyl Stickers', 'Business Cards']),
            'comments' => match ($type) {
                'Comment' => 'Great service and quick turnaround.',
                'Suggestion' => 'It would be nice to have more options.',
                'Complaint' => 'Had to wait longer than expected.',
            },
            'rating' => match ($type) {
                'Comment' => $this->faker->numberBetween(4, 5),
                'Suggestion' => $this->faker->numberBetween(3, 4),
                'Complaint' => $this->faker->numberBetween(1, 2),
            },
            'feedback_date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
        ];
    }
}
