<?php

namespace Database\Seeders\Demo;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Monthly target sales and budget allocations per business, set close to
 * the seeded results so some months beat the target and some miss it.
 * The current Kuwago One target is marked is_displayed for the dashboard gauge.
 */
class TargetSalesSeeder extends DemoSeeder
{
    /** business_type enum value => [report table, sales column, expenses column] */
    private const BUSINESSES = [
        'Kuwago1' => ['kuwago_one_reports', 'sales', 'expenses'],
        'Kuwago2' => ['kuwago_two_reports', 'sales', 'expenses'],
        'UdDesign' => ['uddesign_reports', 'total_sales', 'total_expenses'],
    ];

    protected function tables(): array
    {
        return ['target_sales', 'budget_allocations'];
    }

    protected function seed(): void
    {
        $today = CarbonImmutable::today();
        $now = now();
        $targets = $budgets = [];

        foreach (self::BUSINESSES as $business => [$table, $salesColumn, $expensesColumn]) {
            for ($month = $this->firstDay()->startOfMonth(); $month->lte($today); $month = $month->addMonth()) {
                $end = $month->endOfMonth();
                $totals = DB::table($table)->whereBetween('date', [$month->toDateString(), $end->toDateString()])
                    ->selectRaw("SUM($salesColumn) as sales, SUM($expensesColumn) as expenses, COUNT(*) as days")->first();

                if (! $totals->days) {
                    continue;
                }

                // Scale partial months (the first and the current one) to a full month
                $full = $month->daysInMonth / $totals->days;
                $isCurrent = $month->isSameMonth($today);

                $targets[] = [
                    'business_type' => $business,
                    'amount' => round($totals->sales * $full * $this->jitter(0.92, 1.12), -3),
                    'start_date' => $month->toDateString(),
                    'end_date' => $end->toDateString(),
                    'is_displayed' => $isCurrent && $business === 'Kuwago1',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $budgets[] = [
                    'business_type' => $business,
                    'amount' => round($totals->expenses * $full * $this->jitter(0.95, 1.1), -3),
                    'start_date' => $month->toDateString(),
                    'end_date' => $end->toDateString(),
                    'display_identifier' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->insert('target_sales', $targets);
        $this->insert('budget_allocations', $budgets);
    }
}
