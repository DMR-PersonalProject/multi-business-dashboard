<?php

use App\Models\KuwagoOne\KuwagoOneCategory;
use App\Models\KuwagoOneReport;
use App\Models\User;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    putenv('KUWAGO_ONE_API_URL=http://kuwago-one.test');
    $_ENV['KUWAGO_ONE_API_URL'] = $_SERVER['KUWAGO_ONE_API_URL'] = 'http://kuwago-one.test';

    KuwagoOneReport::create(['sales' => 111, 'date' => '2024-01-01']);
    KuwagoOneCategory::create(['category_id' => 1, 'name' => 'Old category']);
});

afterEach(function () {
    putenv('KUWAGO_ONE_API_URL');
    unset($_ENV['KUWAGO_ONE_API_URL'], $_SERVER['KUWAGO_ONE_API_URL']);
});

function kuwagoOneApiResponses(array $overrides = []): array
{
    $data = array_merge([
        'reports' => [['cash' => 10, 'gcash' => 5, 'total_remittance' => 999, 'date' => '2024-02-01']],
        'categories' => [['id' => 7, 'name' => 'New category']],
        'dishes' => [['id' => 3, 'category_id' => 7, 'name' => 'Dish']],
        'expense-category' => [['id' => 1, 'name' => 'Supplies']],
        'expense-type' => [['id' => 2, 'expense_category_id' => 1, 'name' => 'Cups']],
        'order-details' => [['dish_id' => 3, 'pcs' => 4, 'date' => '2024-02-01']],
        'expense-details' => [['expense_type_id' => 2, 'price' => 50, 'date' => '2024-02-01']],
    ], $overrides);

    $responses = [];
    foreach ($data as $endpoint => $rows) {
        $responses["kuwago-one.test/api/{$endpoint}"] = (is_callable($rows) || $rows instanceof PromiseInterface) ? $rows : Http::response(['data' => $rows]);
    }

    return $responses;
}

function ownerWithKuwagoToken(): array
{
    return [User::factory()->create(['role' => 'owner']), ['kuwago_one_api_token' => 'test-token']];
}

it('no longer exposes the old GET api route', function () {
    $this->get('/api/refresh-data/kuwago_one')->assertNotFound();
});

it('is a rate-limited POST route behind auth and the owner role', function () {
    $route = Route::getRoutes()->getByName('refresh.data');

    expect($route->methods())->toBe(['POST'])
        ->and($route->gatherMiddleware())->toContain('web', 'auth', 'role:owner', 'throttle:5,1');
});

it('redirects guests to login without calling the API', function () {
    Http::fake();

    $this->post('/refresh-data/kuwago_one')->assertRedirect(route('login'));

    Http::assertNothingSent();
    expect(KuwagoOneReport::count())->toBe(1);
});

it('refuses non-owner roles', function (string $role) {
    Http::fake();
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->withSession(['kuwago_one_api_token' => 'test-token'])
        ->post('/refresh-data/kuwago_one')
        ->assertRedirect('/');

    Http::assertNothingSent();
    expect(KuwagoOneReport::first()->sales)->toEqual(111);
})->with(['general', 'kuwago', 'uddesign']);

it('rejects an unknown type', function () {
    [$owner, $session] = ownerWithKuwagoToken();

    $this->actingAs($owner)->withSession($session)
        ->post('/refresh-data/everything')
        ->assertNotFound();
});

it('replaces the data when every response is valid', function () {
    Http::fake(kuwagoOneApiResponses());
    [$owner, $session] = ownerWithKuwagoToken();

    $this->actingAs($owner)->withSession($session)
        ->from('/kuwago-one')
        ->post('/refresh-data/kuwago_one')
        ->assertRedirect('/kuwago-one')
        ->assertSessionHas('status');

    expect(KuwagoOneReport::pluck('sales')->all())->toEqual([999])
        ->and(KuwagoOneCategory::pluck('name')->all())->toBe(['New category']);
});

it('keeps existing data when an endpoint fails', function () {
    Http::fake(kuwagoOneApiResponses(['dishes' => Http::response('Server error', 500)]));
    [$owner, $session] = ownerWithKuwagoToken();

    $this->actingAs($owner)->withSession($session)
        ->post('/refresh-data/kuwago_one')
        ->assertSessionHas('failed');

    expect(KuwagoOneReport::first()->sales)->toEqual(111)
        ->and(KuwagoOneCategory::pluck('name')->all())->toBe(['Old category']);
});

it('keeps existing data when a response has the wrong shape or missing fields', function (array $override) {
    Http::fake(kuwagoOneApiResponses($override));
    [$owner, $session] = ownerWithKuwagoToken();

    $this->actingAs($owner)->withSession($session)
        ->post('/refresh-data/kuwago_one')
        ->assertSessionHas('failed');

    expect(KuwagoOneReport::first()->sales)->toEqual(111)
        ->and(KuwagoOneCategory::count())->toBe(1);
})->with([
    'no data key' => [['categories' => fn () => Http::response(['items' => []])]],
    'missing field' => [['expense-details' => [['price' => 50, 'date' => '2024-02-01']]]],
    'row is not an object' => [['dishes' => ['oops']]],
]);

it('reports a missing API token instead of claiming success', function () {
    Http::fake();
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->post('/refresh-data/kuwago_one')
        ->assertSessionHas('failed');

    Http::assertNothingSent();
    expect(KuwagoOneReport::count())->toBe(1);
});

it('rate limits refreshes', function () {
    Http::fake(kuwagoOneApiResponses());
    [$owner, $session] = ownerWithKuwagoToken();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($owner)->withSession($session)->post('/refresh-data/kuwago_one')->assertRedirect();
    }

    $this->actingAs($owner)->withSession($session)->post('/refresh-data/kuwago_one')->assertStatus(429);
});
