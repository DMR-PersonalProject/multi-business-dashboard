<?php

namespace Database\Factories;

use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fake custom-order deal for UDDesign. Clients, emails and phone numbers are
 * obviously fake (example.com, 0900-555 numbers).
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition()
    {
        $n = $this->faker->unique()->numberBetween(1, 9999);
        $grand = $this->faker->numberBetween(20, 400) * 50;
        $approved = $this->faker->dateTimeBetween('-12 months', '-1 month');

        return [
            'deal_name' => $this->faker->randomElement(['Team Shirts', 'Event Tarpaulins', 'Giveaway Mugs', 'Sticker Pack', 'Uniform Batch']).' #'.$n,
            'client_name' => sprintf('Demo Client %03d', $n),
            'contact_number' => sprintf('0900-555-%04d', $n),
            'email' => sprintf('client%03d@example.com', $n),
            'date_approved' => $approved->format('Y-m-d'),
            'production_due_date' => (clone $approved)->modify('+10 days')->format('Y-m-d'),
            'payment_method' => 'Cash',
            'cash' => $grand,
            'gcash' => 0,
            'cash_gcash' => 0,
            'date_closed' => (clone $approved)->modify('+12 days')->format('Y-m-d'),
            'grand_price' => $grand,
            'status' => 'Closed',
        ];
    }
}
