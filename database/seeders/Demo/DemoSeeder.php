<?php

namespace Database\Seeders\Demo;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Base for the fake demo-data seeders. All data is invented: names are
 * "Demo ..." labels, emails use example.com and phone numbers use 0900-555.
 */
abstract class DemoSeeder extends Seeder
{
    /** Fixed Faker seed so every run produces the same data (relative to today). */
    public const FAKER_SEED = 20261030;

    /** Calendar months of history, ending today (the current month counts as one). */
    public const MONTHS = 12;

    /**
     * Refuse production, skip tables that already hold data, then seed.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(static::class.' creates fake demo data and must not run in production.');
        }

        foreach ($this->tables() as $table) {
            if (DB::table($table)->exists()) {
                $this->command?->warn(class_basename(static::class)." skipped: $table already has data. Use migrate:fresh --seed for a clean demo.");
                return;
            }
        }

        // Each seeder gets its own fixed seed so the businesses don't share one random pattern
        fake()->seed(self::FAKER_SEED + crc32(static::class) % 1000);

        $this->seed();
    }

    /** Tables this seeder fills; it is skipped if any of them is not empty. */
    abstract protected function tables(): array;

    abstract protected function seed(): void;

    protected function faker(): \Faker\Generator
    {
        return fake();
    }

    /** The first day of the demo history: the 1st of the month, so no partial first month. */
    protected function firstDay(): CarbonImmutable
    {
        return CarbonImmutable::today()->startOfMonth()->subMonths(self::MONTHS - 1);
    }

    /** @return CarbonImmutable[] every day from firstDay() to today */
    protected function days(): array
    {
        $first = $this->firstDay();

        return array_map(fn ($i) => $first->addDays($i), range(0, $first->diffInDays(CarbonImmutable::today())));
    }

    /**
     * Demand multiplier around 1.0: weekday profile (ISO 1 = Monday), a
     * holiday-season bump and a gentle growth trend over the year.
     */
    protected function demand(CarbonImmutable $day, array $weekdayProfile): float
    {
        $season = [1 => 0.9, 2 => 0.92, 3 => 0.97, 4 => 1.0, 5 => 1.03, 6 => 0.95,
            7 => 0.93, 8 => 0.95, 9 => 0.98, 10 => 1.0, 11 => 1.06, 12 => 1.25][$day->month];
        $trend = 0.9 + 0.2 * ($this->firstDay()->diffInDays($day) / $this->firstDay()->diffInDays(CarbonImmutable::today()));

        return $weekdayProfile[$day->dayOfWeekIso] * $season * $trend;
    }

    /** Random factor between $min and $max. */
    protected function jitter(float $min = 0.8, float $max = 1.2): float
    {
        return $this->faker()->randomFloat(3, $min, $max);
    }

    /** Timestamp string for rows that belong to a given day. */
    protected function stamp(CarbonImmutable $day, string $time = '20:00:00'): string
    {
        return $day->toDateString().' '.$time;
    }

    /** Bulk insert in chunks to keep the seed fast. */
    protected function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
