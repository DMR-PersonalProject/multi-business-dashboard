<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Feedback;

/**
 * Fake customer feedback; names are "Demo Guest NNN", never real people.
 */
class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition()
    {
        $type = $this->faker->randomElement(['Comment', 'Comment', 'Suggestion', 'Complaint']);

        return [
            'name' => sprintf('Demo Guest %03d', $this->faker->numberBetween(1, 999)),
            'feedback_type' => $type,
            'dish' => $this->faker->randomElement(['Chicken Adobo Bowl', 'Carbonara', 'Iced Latte', 'Mango Float', 'Clubhouse Sandwich']),
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
