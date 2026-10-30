<?php

use App\Models\Feedback;
use App\Models\UddesignFeedback;
use App\Models\User;
use App\Services\GoogleSheetService;
use App\Services\UdGoogleSheetsService;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Feedback::create([
        'name' => 'Old', 'feedback_type' => 'Comment', 'dish' => 'Old dish',
        'comments' => 'Old comment', 'rating' => 3, 'feedback_date' => '2024-01-01',
    ]);
    UddesignFeedback::create([
        'name' => 'Old', 'feedback_type' => 'Comment', 'product_name' => 'Old product',
        'comments' => 'Old comment', 'rating' => 3, 'feedback_date' => '2024-01-01',
    ]);
});

function fakeSheets(mixed $rows): void
{
    foreach ([GoogleSheetService::class, UdGoogleSheetsService::class] as $service) {
        $mock = Mockery::mock($service);
        $rows instanceof Throwable
            ? $mock->shouldReceive('getSheetData')->andThrow($rows)
            : $mock->shouldReceive('getSheetData')->andReturn($rows);
        app()->instance($service, $mock);
    }
}

function validSheetRows(): array
{
    return [
        ['01/02/2024 10:00:00', 'Ana', 'Comment', 'Latte', 'Great', '5'],
        ['02/02/2024 11:30:00', 'Ben', 'Suggestion', 'Waffle', 'More syrup', '4'],
    ];
}

dataset('imports', [
    'kuwago one' => ['/feedback/import-one', Feedback::class, '/kuwago-one/feedbacks'],
    'kuwago two' => ['/feedback/import-two', Feedback::class, '/kuwago-two/feedbacks'],
    'uddesign' => ['/uddesignfeedback/fetch', UddesignFeedback::class, '/uddesign/feedbacks'],
]);

it('exposes imports only as rate-limited owner POST routes', function (string $name) {
    $route = Route::getRoutes()->getByName($name);

    expect($route->methods())->toBe(['POST'])
        ->and($route->gatherMiddleware())->toContain('web', 'auth', 'role:owner', 'throttle:5,1');
})->with(['feedback.import-one', 'feedback.import-two', 'uddesignfeedback.fetch']);

it('does not import on GET', function (string $url, string $model) {
    fakeSheets(validSheetRows());
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)->get($url)->assertStatus(405);

    expect($model::pluck('name')->all())->toBe(['Old']);
})->with('imports');

it('refuses guests and non-owners', function (string $url, string $model) {
    fakeSheets(validSheetRows());

    $this->post($url)->assertRedirect(route('login'));

    foreach (['general', 'kuwago', 'uddesign'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->post($url)->assertRedirect('/');
    }

    expect($model::pluck('name')->all())->toBe(['Old']);
})->with('imports');

it('replaces feedback when every row is valid', function (string $url, string $model, string $page) {
    fakeSheets(validSheetRows());

    $this->actingAs(User::factory()->create(['role' => 'owner']))
        ->post($url)
        ->assertRedirect($page)
        ->assertSessionHas('success');

    expect($model::orderBy('name')->pluck('name')->all())->toBe(['Ana', 'Ben']);
})->with('imports');

it('keeps existing feedback when the sheet data is invalid', function (mixed $rows) {
    fakeSheets($rows);
    $owner = User::factory()->create(['role' => 'owner']);

    foreach (['/feedback/import-one', '/feedback/import-two', '/uddesignfeedback/fetch'] as $url) {
        $this->actingAs($owner)->post($url)->assertSessionHas('failed');
    }

    expect(Feedback::pluck('name')->all())->toBe(['Old'])
        ->and(UddesignFeedback::pluck('name')->all())->toBe(['Old']);
})->with([
    'empty sheet' => [null],
    'incomplete row' => [[['01/02/2024 10:00:00', 'Ana', 'Comment']]],
    'bad date' => [[['yesterday', 'Ana', 'Comment', 'Latte', 'Great', '5']]],
    'bad rating' => [[['01/02/2024 10:00:00', 'Ana', 'Comment', 'Latte', 'Great', 'ten']]],
    'sheet error' => [new RuntimeException('Sheets API unavailable')],
]);
