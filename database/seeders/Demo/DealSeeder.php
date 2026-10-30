<?php

namespace Database\Seeders\Demo;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * UDDesign custom-order deals and their items. Older deals are mostly closed,
 * recent ones are open or in production. grand_price is the sum of the items.
 * Clients are "Demo Client NNN" with example.com emails and 0900-555 numbers.
 */
class DealSeeder extends DemoSeeder
{
    private const PRODUCTS = [
        ['Custom printed T-shirt (front and back)', 280, 380, 20, 150],
        ['Embroidered polo shirt', 450, 600, 10, 60],
        ['Event tarpaulin 3x6 ft', 400, 500, 1, 6],
        ['Sublimated mug with logo', 180, 240, 12, 100],
        ['Die-cut sticker set (50 pcs)', 300, 400, 5, 40],
        ['Printed tote bag', 150, 220, 20, 120],
        ['ID lanyard with print', 70, 110, 30, 200],
        ['Souvenir keychain', 45, 70, 50, 300],
        ['Hoodie with screen print', 750, 950, 5, 40],
        ['Roll-up standee banner', 1800, 2500, 1, 3],
    ];

    private const NAMES = ['Team Shirts', 'Event Tarpaulins', 'Giveaway Mugs', 'Sticker Pack', 'Uniform Batch',
        'Org Merch Bundle', 'Seminar Kits', 'Fun Run Shirts', 'Reunion Souvenirs', 'Store Signage'];

    protected function tables(): array
    {
        return ['deals', 'deal_items'];
    }

    protected function seed(): void
    {
        $faker = $this->faker();
        $today = CarbonImmutable::today();
        $n = 0;

        foreach ($this->days() as $day) {
            // About three new deals a week, fewer on Sundays
            if (! $faker->boolean($day->isSunday() ? 15 : 45)) {
                continue;
            }

            $n++;
            $age = $day->diffInDays($today);
            $status = $this->statusFor($age);

            $items = [];
            foreach ($faker->randomElements(self::PRODUCTS, $faker->numberBetween(1, 3)) as [$desc, $min, $max, $qMin, $qMax]) {
                $qty = $faker->numberBetween($qMin, $qMax);
                $unit = $faker->numberBetween($min / 5, $max / 5) * 5;
                $items[] = ['description' => $desc, 'quantity' => $qty, 'unit_price' => $unit, 'total_price' => $qty * $unit];
            }
            $grand = array_sum(array_column($items, 'total_price'));

            $approved = $status === 'Open' ? null : $day->addDays($faker->numberBetween(0, 2))->min($today);
            $due = $approved?->addDays($faker->numberBetween(5, 14));
            $closed = $status === 'Closed' ? $due->addDays($faker->numberBetween(-2, 3))->max($approved)->min($today) : null;

            // Closed deals are paid in full; deals in production carry a 50% down payment
            $paid = match ($status) {
                'Closed' => $grand,
                'Processing', 'On-Hold' => round($grand / 2, 2),
                default => null,
            };
            $method = $paid === null ? null : $faker->randomElement(['Cash', 'Cash', 'GCash', 'GCash', 'Cash_GCash']);
            $cashPart = $method === 'Cash_GCash' ? round($paid * $faker->randomFloat(2, 0.3, 0.7), 2) : null;

            $stamp = $this->stamp($day, sprintf('%02d:%02d:00', $faker->numberBetween(9, 18), $faker->numberBetween(0, 59)));
            $dealId = DB::table('deals')->insertGetId([
                'deal_name' => $faker->randomElement(self::NAMES).' #'.str_pad($n, 3, '0', STR_PAD_LEFT),
                'client_name' => sprintf('Demo Client %03d', $faker->numberBetween(1, 120)),
                'contact_number' => sprintf('0900-555-%04d', $faker->numberBetween(1, 9999)),
                'email' => $faker->boolean(80) ? sprintf('client%03d@example.com', $n) : null,
                'date_approved' => $approved?->toDateString(),
                'production_due_date' => $due?->toDateString(),
                'payment_method' => $method,
                'cash' => match ($method) { 'Cash' => $paid, 'Cash_GCash' => $cashPart, 'GCash' => 0, default => null },
                'gcash' => match ($method) { 'GCash' => $paid, 'Cash_GCash' => $paid - $cashPart, 'Cash' => 0, default => null },
                'cash_gcash' => $method === 'Cash_GCash' ? $paid : ($method ? 0 : null),
                'date_closed' => $closed?->toDateString(),
                'grand_price' => $grand,
                'status' => $status,
                'created_at' => $stamp,
                'updated_at' => $closed ? $this->stamp($closed) : $stamp,
            ]);

            $this->insert('deal_items', array_map(
                fn ($item) => $item + ['deal_id' => $dealId, 'created_at' => $stamp, 'updated_at' => $stamp],
                $items
            ));
        }
    }

    private function statusFor(int $age): string
    {
        $pick = $this->faker()->numberBetween(1, 100);

        if ($age > 30) {
            return $pick <= 86 ? 'Closed' : ($pick <= 95 ? 'Cancelled' : 'On-Hold');
        }
        if ($age > 12) {
            return $pick <= 55 ? 'Closed' : ($pick <= 80 ? 'Processing' : ($pick <= 90 ? 'On-Hold' : 'Cancelled'));
        }

        return $pick <= 40 ? 'Open' : ($pick <= 85 ? 'Processing' : 'On-Hold');
    }
}
