<?php

namespace Database\Seeders\Demo;

/**
 * Customer feedback for the Kuwago restaurants (one shared feedback table)
 * and for UDDesign. Reviewers are "Demo Guest NNN"; ratings follow the type.
 */
class FeedbackSeeder extends DemoSeeder
{
    private const TEMPLATES = [
        'Comment' => [
            'The %s was really good, will order again.',
            'Loved the %s. Friendly staff too.',
            'Great value for the %s.',
            '%s came out fast and tasted great.',
            'My go-to order is the %s. Always consistent.',
            'The %s exceeded what I expected.',
        ],
        'Suggestion' => [
            'Maybe offer a bigger size for the %s?',
            'Please add more options similar to the %s.',
            'The %s would be perfect with a combo deal.',
            'Would love to see the %s available for delivery.',
        ],
        'Complaint' => [
            'Waited too long for the %s.',
            'The %s was not the same as last time.',
            'The %s was a bit overpriced for the size.',
            'Got the wrong %s on my order.',
        ],
    ];

    protected function tables(): array
    {
        return ['feedback', 'uddesign_feedback'];
    }

    protected function seed(): void
    {
        $dishes = [];
        foreach ([KuwagoOneSeeder::menu(), KuwagoTwoSeeder::menu()] as $menu) {
            foreach ($menu as $items) {
                array_push($dishes, ...array_column($items, 0));
            }
        }

        $products = [];
        foreach ([UddesignSeeder::PRINT, UddesignSeeder::MERCH] as $catalog) {
            foreach ($catalog as $items) {
                array_push($products, ...array_column($items, 0));
            }
        }

        $this->insert('feedback', $this->rows('dish', $dishes, 1.3));
        $this->insert('uddesign_feedback', $this->rows('product_name', $products, 0.7));
    }

    /** About $perDay feedback entries a day across the whole history. */
    private function rows(string $itemColumn, array $items, float $perDay): array
    {
        $faker = $this->faker();
        $rows = [];

        foreach ($this->days() as $day) {
            $count = (int) floor($perDay) + ($faker->randomFloat(2, 0, 1) < fmod($perDay, 1) ? 1 : 0);

            for ($i = 0; $i < $count; $i++) {
                $type = $faker->randomElement(['Comment', 'Comment', 'Comment', 'Comment', 'Suggestion', 'Suggestion', 'Complaint']);
                $item = $faker->randomElement($items);
                $stamp = $this->stamp($day, sprintf('%02d:%02d:00', $faker->numberBetween(9, 21), $faker->numberBetween(0, 59)));

                $rows[] = [
                    'name' => sprintf('Demo Guest %03d', $faker->numberBetween(1, 400)),
                    'feedback_type' => $type,
                    $itemColumn => $item,
                    'comments' => sprintf($faker->randomElement(self::TEMPLATES[$type]), $item),
                    'rating' => match ($type) {
                        'Comment' => $faker->randomElement([4, 5, 5]),
                        'Suggestion' => $faker->numberBetween(3, 4),
                        'Complaint' => $faker->numberBetween(1, 2),
                    },
                    'feedback_date' => $day->toDateString(),
                    'created_at' => $stamp,
                    'updated_at' => $stamp,
                ];
            }
        }

        return $rows;
    }
}
