<?php

use App\Models\BusinessInfo;
use App\Models\Deal;
use App\Models\Feedback;
use App\Models\TargetSales;
use App\Models\UddesignFeedback;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Demo\BusinessInfoSeeder;
use Database\Seeders\Demo\KuwagoOneSeeder;
use Illuminate\Support\Facades\DB;

it('fills the main tables with about 12 months of consistent fake data', function () {
    $this->seed(DatabaseSeeder::class);

    $tables = ['users', 'business_infos', 'target_sales', 'budget_allocations', 'promos', 'deals', 'deal_items',
        'feedback', 'uddesign_feedback',
        'kuwago_one_reports', 'kuwago_one_categories', 'kuwago_one_dishes', 'kuwago_one_order_details',
        'kuwago_one_expense_categories', 'kuwago_one_expense_types', 'kuwago_one_expense_details',
        'kuwago_two_reports', 'kuwago_two_categories', 'kuwago_two_dishes', 'kuwago_two_order_details',
        'kuwago_two_expense_categories', 'kuwago_two_expense_types', 'kuwago_two_expense_details',
        'uddesign_reports', 'uddesign_print_categories', 'uddesign_print_types', 'uddesign_print_details',
        'uddesign_merch_categories', 'uddesign_merch_types', 'uddesign_merch_details',
        'uddesign_expense_categories', 'uddesign_expense_types', 'uddesign_expense_details'];
    foreach ($tables as $table) {
        expect(DB::table($table)->count())->toBeGreaterThan(0, "$table is empty");
    }

    // Twelve calendar months ending today
    expect(DB::table('kuwago_one_reports')->min('date'))->toBe(today()->startOfMonth()->subMonths(11)->toDateString());
    expect(DB::table('kuwago_one_reports')->max('date'))->toBe(today()->toDateString());

    // The dashboard gauge has a target: this month's Kuwago One target
    $displayed = TargetSales::where('is_displayed', true)->get();
    expect($displayed)->toHaveCount(1);
    expect($displayed->first()->business_type)->toBe('Kuwago1');
    expect($displayed->first()->start_date)->toBe(today()->startOfMonth()->toDateString());

    // Reports agree with their detail rows
    expect((int) DB::table('kuwago_one_reports')->sum('expenses'))
        ->toBe((int) DB::table('kuwago_one_expense_details')->sum('price'));
    expect((int) DB::table('uddesign_reports')->sum('total_expenses'))
        ->toBe((int) DB::table('uddesign_expense_details')->sum('price'));
    expect(round((float) DB::table('uddesign_reports')->sum('custom_sales'), 2))
        ->toBe(round((float) Deal::where('status', 'Closed')->sum('grand_price'), 2));

    // Only obviously fake contact details
    expect(Deal::where('email', 'not like', '%@example.com')->count())->toBe(0);
    expect(Deal::where('contact_number', 'not like', '0900-555-%')->count())->toBe(0);
    expect(Feedback::where('name', 'not like', 'Demo Guest %')->count())->toBe(0);
    expect(UddesignFeedback::where('name', 'not like', 'Demo Guest %')->count())->toBe(0);
});

it('produces the same data on every run', function () {
    $tables = ['kuwago_one_reports', 'kuwago_one_order_details', 'kuwago_one_expense_details',
        'kuwago_one_dishes', 'kuwago_one_categories', 'kuwago_one_expense_types', 'kuwago_one_expense_categories'];
    $snapshot = fn () => DB::table('kuwago_one_reports')->orderBy('id')->get(['date', 'orders', 'cash', 'gcash', 'sales', 'expenses'])->toJson();

    $this->seed(KuwagoOneSeeder::class);
    $first = $snapshot();

    foreach ($tables as $table) {
        DB::table($table)->delete();
    }
    $this->seed(KuwagoOneSeeder::class);

    expect($snapshot())->toBe($first);
});

it('skips tables that already have data', function () {
    $this->seed(BusinessInfoSeeder::class);
    $this->seed(BusinessInfoSeeder::class);

    expect(BusinessInfo::count())->toBe(3);
});

it('refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new BusinessInfoSeeder)->run())->toThrow(RuntimeException::class);
    expect(fn () => (new DatabaseSeeder)->run())->toThrow(RuntimeException::class);
    expect(BusinessInfo::count())->toBe(0);
});
