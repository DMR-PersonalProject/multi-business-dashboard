<?php

namespace Database\Seeders\Demo;

use App\Models\Promo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Kuwago promos (the promos table is shared by both restaurants). The schedule
 * is fixed so RestaurantSeeder can lift demand slightly on promo days; the
 * before/after sales come from the seeded Kuwago reports.
 */
class PromoSeeder extends DemoSeeder
{
    /** [title, days ago it started, length in days, dishes] — the last one is still running. */
    private const SCHEDULE = [
        ['Rainy Day Rice Bowl Combo', 340, 14, 'Chicken Adobo Bowl, Tocino Silog, Iced Lemon Tea'],
        ['Buy 1 Take 1 Iced Coffee', 300, 10, 'Spanish Latte, Iced Latte, Iced Mocha'],
        ['Student Meal Deal', 262, 21, 'Pork Sisig Rice, Fries Basket, Calamansi Juice'],
        ['Summer Halo-Halo Fest', 220, 14, 'Halo-Halo, Mango Float, Mango Shake'],
        ['Pasta Wednesdays', 185, 21, 'Carbonara, Aglio Olio, Truffle Cream Pasta'],
        ['Merienda Treats', 150, 10, 'Lumpiang Shanghai, Banana Turon a la Mode, Leche Flan'],
        ['Weekend Family Bundle', 115, 14, 'Chicken Inasal, Baked Mac, Chicken Wings (6 pcs)'],
        ['Back-to-School Brew', 80, 14, 'Cafe Latte, Salted Caramel Latte, Matcha Latte'],
        ['Payday Feast', 45, 7, 'Beef Tapa, Pork Tonkatsu, Ube Cheesecake'],
        ['Holiday Countdown Specials', 5, 14, 'Chocolate Lava Cake, Caramel Macchiato, Garlic Butter Shrimp'],
    ];

    /** @return array<int, array{title:string, start:CarbonImmutable, end:CarbonImmutable, dishes:string}> */
    public static function windows(): array
    {
        return array_map(function ($p) {
            $start = CarbonImmutable::today()->subDays($p[1]);

            return ['title' => $p[0], 'start' => $start, 'end' => $start->addDays($p[2] - 1), 'dishes' => $p[3]];
        }, self::SCHEDULE);
    }

    /** @return array<string, true> every promo date (Y-m-d) */
    public static function promoDays(): array
    {
        $days = [];
        foreach (self::windows() as $w) {
            for ($d = $w['start']; $d->lte($w['end']); $d = $d->addDay()) {
                $days[$d->toDateString()] = true;
            }
        }

        return $days;
    }

    protected function tables(): array
    {
        return ['promos'];
    }

    protected function seed(): void
    {
        foreach (self::windows() as $w) {
            $length = $w['start']->diffInDays($w['end']) + 1;

            Promo::factory()->create([
                'title' => $w['title'],
                'start_date' => $w['start']->toDateString(),
                'end_date' => $w['end']->toDateString(),
                // Same number of days just before the promo vs. during it (up to today)
                'sales_before' => $this->kuwagoSales($w['start']->subDays($length), $w['start']->subDay()),
                'sales_after' => $this->kuwagoSales($w['start'], $w['end']->min(CarbonImmutable::today())),
                'description' => "Demo promo for both Kuwago branches, running {$length} days.",
                'dishes_available' => $w['dishes'],
                'created_at' => $w['start']->subDays(3),
                'updated_at' => $w['start']->subDays(3),
            ]);
        }
    }

    private function kuwagoSales(CarbonImmutable $from, CarbonImmutable $to): float
    {
        $range = [$from->toDateString(), $to->toDateString()];

        return round(DB::table('kuwago_one_reports')->whereBetween('date', $range)->sum('sales')
            + DB::table('kuwago_two_reports')->whereBetween('date', $range)->sum('sales'), 2);
    }
}
