<?php

namespace Database\Seeders\Demo;

use Carbon\CarbonImmutable;

/**
 * Menu, order lines, expenses and daily reports for one Kuwago restaurant.
 *
 * Keys follow the controllers' joins: order_details.dish_id -> dishes.id,
 * dishes.category_id -> categories.category_id,
 * expense_details.expense_type_id -> expense_types.expense_type_id and
 * expense_types.expense_category_id -> expense_categories.expense_category_id.
 * The report for each day uses the same numbers: sales = sum of pcs x price,
 * expenses = sum of that day's expense entries.
 */
abstract class RestaurantSeeder extends DemoSeeder
{
    /** Table prefix, for example "kuwago_one". */
    abstract protected function prefix(): string;

    /** Overall size of the restaurant relative to Kuwago One. */
    abstract protected function scale(): float;

    /** [category name => [[dish name, price, average pcs per day], ...]] */
    abstract public static function menu(): array;

    /** Busier on Fridays and weekends. */
    protected const WEEKDAYS = [1 => 0.82, 2 => 0.85, 3 => 0.9, 4 => 0.95, 5 => 1.15, 6 => 1.35, 7 => 1.25];

    /** [category => [type, ...]], ids are assigned in order. */
    protected const EXPENSES = [
        'Ingredients' => ['Rice and Grains', 'Meat and Poultry', 'Seafood', 'Vegetables and Fruits', 'Coffee Beans', 'Dairy'],
        'Utilities' => ['Electricity', 'Water', 'Internet'],
        'Salaries' => ['Kitchen Staff', 'Service Staff'],
        'Supplies' => ['Packaging', 'Cleaning Supplies'],
        'Rent' => ['Space Rent'],
        'Maintenance' => ['Equipment Repair'],
    ];

    protected function tables(): array
    {
        $p = $this->prefix();

        return ["{$p}_categories", "{$p}_dishes", "{$p}_order_details", "{$p}_expense_categories",
            "{$p}_expense_types", "{$p}_expense_details", "{$p}_reports"];
    }

    protected function seed(): void
    {
        $p = $this->prefix();
        $dishes = $this->seedMenu();
        $types = $this->seedExpenseTypes();
        $promoDays = PromoSeeder::promoDays();

        $orders = $expenses = $reports = [];

        foreach ($this->days() as $day) {
            $stamp = $this->stamp($day);
            $demand = $this->demand($day, static::WEEKDAYS) * $this->scale()
                * (isset($promoDays[$day->toDateString()]) ? 1.12 : 1.0);

            $sales = 0;
            $pcsTotal = 0;
            foreach ($dishes as $dish) {
                $pcs = (int) round($dish['avg'] * $demand * $this->jitter(0.5, 1.5));
                if ($pcs > 0) {
                    $orders[] = ['dish_id' => $dish['id'], 'pcs' => $pcs, 'date' => $day->toDateString(), 'created_at' => $stamp, 'updated_at' => $stamp];
                    $sales += $pcs * $dish['price'];
                    $pcsTotal += $pcs;
                }
            }

            $dayExpenses = $this->expensesFor($day, $sales, $types);
            foreach ($dayExpenses as [$typeId, $price]) {
                $expenses[] = ['expense_type_id' => $typeId, 'price' => $price, 'date' => $day->toDateString(), 'created_at' => $stamp, 'updated_at' => $stamp];
            }

            $cash = round($sales * $this->jitter(0.55, 0.7));
            $reports[] = [
                'orders' => max(1, (int) round($pcsTotal / $this->jitter(1.6, 2.1))),
                'cash' => $cash,
                'gcash' => $sales - $cash,
                'sales' => $sales,
                'expenses' => array_sum(array_column($dayExpenses, 1)),
                'date' => $day->toDateString(),
                'created_at' => $stamp,
                'updated_at' => $stamp,
            ];
        }

        $this->insert("{$p}_order_details", $orders);
        $this->insert("{$p}_expense_details", $expenses);
        $this->insert("{$p}_reports", $reports);
    }

    /** @return array<int, array{id:int, price:int, avg:float}> */
    private function seedMenu(): array
    {
        $p = $this->prefix();
        $now = now();
        $categories = $dishRows = $dishes = [];
        $dishId = 0;

        foreach (array_keys(static::menu()) as $i => $category) {
            $categoryId = $i + 1;
            $categories[] = ['id' => $categoryId, 'category_id' => $categoryId, 'name' => $category, 'created_at' => $now, 'updated_at' => $now];

            foreach (static::menu()[$category] as [$name, $price, $avg]) {
                $dishId++;
                // id = dish_id so both columns identify the same dish
                $dishRows[] = ['id' => $dishId, 'dish_id' => $dishId, 'category_id' => $categoryId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
                $dishes[] = ['id' => $dishId, 'price' => $price, 'avg' => $avg];
            }
        }

        $this->insert("{$p}_categories", $categories);
        $this->insert("{$p}_dishes", $dishRows);

        return $dishes;
    }

    /** @return array<string, int> expense type name => expense_type_id */
    private function seedExpenseTypes(): array
    {
        $p = $this->prefix();
        $now = now();
        $categories = $typeRows = $types = [];
        $typeId = 0;

        foreach (array_keys(static::EXPENSES) as $i => $category) {
            $categoryId = $i + 1;
            $categories[] = ['id' => $categoryId, 'expense_category_id' => $categoryId, 'name' => $category, 'created_at' => $now, 'updated_at' => $now];

            foreach (static::EXPENSES[$category] as $name) {
                $typeId++;
                $typeRows[] = ['id' => $typeId, 'expense_type_id' => $typeId, 'expense_category_id' => $categoryId, 'name' => $name, 'created_at' => $now, 'updated_at' => $now];
                $types[$name] = $typeId;
            }
        }

        $this->insert("{$p}_expense_categories", $categories);
        $this->insert("{$p}_expense_types", $typeRows);

        return $types;
    }

    /**
     * Daily ingredient buys (about a third of sales), weekly supplies, monthly
     * rent and utilities, twice-monthly payroll and the odd repair.
     *
     * @return array<int, array{0:int, 1:int}> [expense_type_id, price]
     */
    private function expensesFor(CarbonImmutable $day, int $sales, array $types): array
    {
        $s = $this->scale();
        $rows = [];

        $ingredients = ['Rice and Grains', 'Meat and Poultry', 'Seafood', 'Vegetables and Fruits', 'Coffee Beans', 'Dairy'];
        $budget = $sales * $this->jitter(0.3, 0.36);
        $bought = $this->faker()->randomElements($ingredients, $this->faker()->numberBetween(2, 4));
        foreach ($bought as $name) {
            $rows[] = [$types[$name], (int) round($budget / count($bought))];
        }

        if ($day->isMonday()) {
            $rows[] = [$types['Packaging'], (int) round(2200 * $s * $this->jitter())];
            $rows[] = [$types['Cleaning Supplies'], (int) round(900 * $s * $this->jitter())];
        }
        if ($day->day === 1) {
            $rows[] = [$types['Space Rent'], (int) round(40000 * $s)];
        }
        if ($day->day === 5) {
            $rows[] = [$types['Electricity'], (int) round(14000 * $s * $this->jitter(0.85, 1.2))];
            $rows[] = [$types['Water'], (int) round(2000 * $s * $this->jitter())];
            $rows[] = [$types['Internet'], 1999];
        }
        if ($day->day === 15 || $day->isLastOfMonth()) {
            $rows[] = [$types['Kitchen Staff'], (int) round(26000 * $s)];
            $rows[] = [$types['Service Staff'], (int) round(19000 * $s)];
        }
        if ($this->faker()->boolean(4)) {
            $rows[] = [$types['Equipment Repair'], $this->faker()->numberBetween(15, 60) * 100];
        }

        return $rows;
    }
}
