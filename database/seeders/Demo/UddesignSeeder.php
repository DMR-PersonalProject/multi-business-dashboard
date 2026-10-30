<?php

namespace Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;

/**
 * UDDesign print and merch catalogs, daily print/merch lines, expenses and
 * daily reports. Keys follow the controller joins (*_details.*_type_id ->
 * *_types.*_type_id -> *_categories.*_category_id). Custom sales are the
 * deals closed that day, so DealSeeder must run first.
 */
class UddesignSeeder extends DemoSeeder
{
    /** [category => [[type, price, average pcs per day], ...]] */
    public const PRINT = [
        'Tarpaulin' => [['Tarpaulin 2x3 ft', 180, 2], ['Tarpaulin 3x6 ft', 450, 1.5], ['Tarpaulin 4x8 ft', 850, 0.5]],
        'Stickers' => [['Vinyl Sticker Sheet', 120, 4], ['Die-cut Stickers (50 pcs)', 350, 1], ['Label Stickers (roll)', 280, 0.8]],
        'Business Cards' => [['Business Cards (100 pcs)', 350, 1], ['Premium Business Cards (100 pcs)', 600, 0.4]],
        'Document Printing' => [['B&W Print (A4)', 5, 90], ['Colored Print (A4)', 15, 40], ['Photo Print 4R', 25, 15], ['Lamination (A4)', 30, 8]],
    ];

    public const MERCH = [
        'Apparel' => [['Printed T-Shirt', 350, 3], ['Hoodie', 850, 0.6], ['Polo Shirt', 550, 0.8]],
        'Drinkware' => [['Sublimated Mug', 220, 2.5], ['Tumbler', 380, 1]],
        'Accessories' => [['Tote Bag', 180, 1.5], ['Keychain', 60, 4], ['Button Pin', 35, 5], ['Lanyard', 90, 2], ['Cap', 300, 0.7]],
    ];

    /** Expense types per report bucket, with the share of that bucket's sales they cost. */
    private const EXPENSES = [
        'Print Materials' => ['types' => ['Ink and Toner', 'Paper', 'Tarpaulin Roll', 'Sticker Vinyl'], 'share' => [0.34, 0.42], 'bucket' => 'print'],
        'Merch Blanks' => ['types' => ['Blank Shirts', 'Blank Mugs', 'Bags and Accessories'], 'share' => [0.44, 0.52], 'bucket' => 'merch'],
        'Custom Job Materials' => ['types' => ['Sublimation Supplies', 'Heat Transfer Vinyl', 'Packaging'], 'share' => [0.4, 0.5], 'bucket' => 'custom'],
    ];

    /** Fixed costs; the reports have no overhead column, so they count as print expenses. */
    private const OVERHEAD = ['Shop Rent', 'Electricity', 'Staff Salaries'];

    /** Busy on weekdays, quiet on Sundays. */
    private const WEEKDAYS = [1 => 1.1, 2 => 1.05, 3 => 1.05, 4 => 1.05, 5 => 1.15, 6 => 0.9, 7 => 0.35];

    protected function tables(): array
    {
        return ['uddesign_print_categories', 'uddesign_print_types', 'uddesign_print_details',
            'uddesign_merch_categories', 'uddesign_merch_types', 'uddesign_merch_details',
            'uddesign_expense_categories', 'uddesign_expense_types', 'uddesign_expense_details', 'uddesign_reports'];
    }

    protected function seed(): void
    {
        $printTypes = $this->seedCatalog('print', self::PRINT);
        $merchTypes = $this->seedCatalog('merch', self::MERCH);
        $expenseTypes = $this->seedExpenseTypes();

        $custom = DB::table('deals')->where('status', 'Closed')
            ->selectRaw('date_closed, SUM(grand_price) as total')->groupBy('date_closed')
            ->pluck('total', 'date_closed')->all();

        $prints = $merches = $expenses = $reports = [];

        foreach ($this->days() as $day) {
            $date = $day->toDateString();
            $stamp = $this->stamp($day, '19:00:00');
            $demand = $this->demand($day, self::WEEKDAYS);

            [$dayPrints, $printSales] = $this->lines('print', $printTypes, $demand, $date, $stamp);
            [$dayMerches, $merchSales] = $this->lines('merch', $merchTypes, $demand, $date, $stamp);
            array_push($prints, ...$dayPrints);
            array_push($merches, ...$dayMerches);
            $sales = ['print' => $printSales, 'merch' => $merchSales, 'custom' => (float) ($custom[$date] ?? 0)];

            // Each bucket's cost is a share of its sales, split over one or two expense types
            $costs = ['print' => 0, 'merch' => 0, 'custom' => 0];
            foreach (self::EXPENSES as $category => $spec) {
                $bucket = $spec['bucket'];
                if ($sales[$bucket] <= 0) {
                    continue;
                }
                $amount = $sales[$bucket] * $this->jitter(...$spec['share']);
                $picked = $this->faker()->randomElements($spec['types'], $this->faker()->numberBetween(1, 2));
                foreach ($picked as $type) {
                    $price = (int) round($amount / count($picked));
                    $expenses[] = ['expense_type_id' => $expenseTypes[$type], 'price' => $price, 'date' => $date, 'created_at' => $stamp, 'updated_at' => $stamp];
                    $costs[$bucket] += $price;
                }
            }

            $overhead = array_filter([
                'Shop Rent' => $day->day === 1 ? 25000 : 0,
                'Electricity' => $day->day === 5 ? (int) round(9000 * $this->jitter(0.85, 1.2)) : 0,
                'Staff Salaries' => $day->day === 15 || $day->isLastOfMonth() ? 18000 : 0,
            ]);
            foreach ($overhead as $type => $price) {
                $expenses[] = ['expense_type_id' => $expenseTypes[$type], 'price' => $price, 'date' => $date, 'created_at' => $stamp, 'updated_at' => $stamp];
                $costs['print'] += $price;
            }

            $total = array_sum($sales);
            $cash = round($total * $this->jitter(0.35, 0.6), 2);
            $reports[] = [
                'gcash' => $total - $cash,
                'cash' => $cash,
                'print_sales' => $sales['print'],
                'merch_sales' => $sales['merch'],
                'custom_sales' => $sales['custom'],
                'total_sales' => $total,
                'print_expenses' => $costs['print'],
                'merch_expenses' => $costs['merch'],
                'custom_expenses' => $costs['custom'],
                'total_expenses' => array_sum($costs),
                'date' => $date,
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ];
        }

        $this->insert('uddesign_print_details', $prints);
        $this->insert('uddesign_merch_details', $merches);
        $this->insert('uddesign_expense_details', $expenses);
        $this->insert('uddesign_reports', $reports);
    }

    /** @return array{0: array, 1: int} the day's detail rows for one catalog and their sales total */
    private function lines(string $kind, array $types, float $demand, string $date, string $stamp): array
    {
        $rows = [];
        $sales = 0;

        foreach ($types as $type) {
            $pcs = (int) round($type['avg'] * $demand * $this->jitter(0.3, 1.7));
            if ($pcs > 0) {
                $rows[] = ["{$kind}_type_id" => $type['id'], 'pcs' => $pcs, 'date' => $date, 'created_at' => $stamp, 'updated_at' => $stamp];
                $sales += $pcs * $type['price'];
            }
        }

        return [$rows, $sales];
    }

    /** @return array<int, array{id:int, price:int, avg:float}> *_type_id, price and daily average */
    private function seedCatalog(string $kind, array $catalog): array
    {
        $now = now();
        $categories = $typeRows = $types = [];
        $typeId = 0;

        foreach (array_keys($catalog) as $i => $category) {
            $categoryId = $i + 1;
            $categories[] = ['id' => $categoryId, "{$kind}_category_id" => $categoryId, 'name' => $category, 'created_at' => $now, 'updated_at' => $now];

            foreach ($catalog[$category] as [$name, $price, $avg]) {
                $typeId++;
                $typeRows[] = ['id' => $typeId, "{$kind}_type_id" => $typeId, "{$kind}_category_id" => $categoryId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
                $types[] = ['id' => $typeId, 'price' => $price, 'avg' => $avg];
            }
        }

        $this->insert("uddesign_{$kind}_categories", $categories);
        $this->insert("uddesign_{$kind}_types", $typeRows);

        return $types;
    }

    /** @return array<string, int> expense type name => expense_type_id */
    private function seedExpenseTypes(): array
    {
        $now = now();
        $categories = $typeRows = $types = [];
        $typeId = 0;

        $catalog = array_map(fn ($spec) => $spec['types'], self::EXPENSES) + ['Shop Overhead' => self::OVERHEAD];

        foreach (array_keys($catalog) as $i => $category) {
            $categoryId = $i + 1;
            $categories[] = ['id' => $categoryId, 'expense_category_id' => $categoryId, 'name' => $category, 'created_at' => $now, 'updated_at' => $now];

            foreach ($catalog[$category] as $name) {
                $typeId++;
                $typeRows[] = ['id' => $typeId, 'expense_type_id' => $typeId, 'expense_category_id' => $categoryId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
                $types[$name] = $typeId;
            }
        }

        $this->insert('uddesign_expense_categories', $categories);
        $this->insert('uddesign_expense_types', $typeRows);

        return $types;
    }
}
