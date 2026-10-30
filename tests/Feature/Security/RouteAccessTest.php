<?php

use App\Models\Deal;
use App\Models\TargetSales;
use App\Models\User;
use App\Services\GoogleSheetService;
use App\Services\UdGoogleSheetsService;

beforeEach(function () {
    // The feedback controllers need these services; the real ones read a key file.
    app()->instance(GoogleSheetService::class, Mockery::mock(GoogleSheetService::class));
    app()->instance(UdGoogleSheetsService::class, Mockery::mock(UdGoogleSheetsService::class));
});

dataset('previously open routes', [
    'deals index' => ['get', '/deals'],
    'deals create' => ['get', '/deals/create'],
    'deals store' => ['post', '/deals'],
    'fetch target sale' => ['get', '/fetch-target-sale'],
    'target sale display' => ['post', '/target-sales/display/1'],
    'kuwago feedback page' => ['get', '/feedback'],
    'kuwago feedback filter' => ['post', '/feedback/filter-by-date'],
    'uddesign feedback page' => ['get', '/uddesignfeedback'],
    'uddesign feedback filter' => ['post', '/uddesignfeedback/filter-by-date'],
]);

it('sends guests to the login page', function (string $method, string $uri) {
    $this->{$method}($uri)->assertRedirect(route('login'));
})->with('previously open routes');

it('lets owner, general and uddesign roles use deals; kuwago is redirected', function () {
    // An empty form reaches validation only if the role middleware let the request through.
    foreach (['owner', 'general', 'uddesign'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post('/deals', [])
            ->assertSessionHasErrors('deal_name');
    }

    $this->actingAs(User::factory()->create(['role' => 'kuwago']))
        ->post('/deals', [])
        ->assertRedirect('/kuwago-one')
        ->assertSessionHasNoErrors();
});

it('does not let the kuwago role change or delete deals', function () {
    $deal = Deal::factory()->create();

    $this->actingAs(User::factory()->create(['role' => 'kuwago']))
        ->delete("/deals/{$deal->id}")
        ->assertRedirect('/kuwago-one');

    expect(Deal::find($deal->id))->not->toBeNull();
});

it('lets owner, general and kuwago roles open Kuwago feedback; uddesign is redirected', function () {
    foreach (['owner', 'general', 'kuwago'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get('/feedback')->assertOk();
    }

    $this->actingAs(User::factory()->create(['role' => 'uddesign']))->get('/feedback')->assertRedirect('/uddesign');
});

it('lets owner, general and uddesign roles filter UDDesign feedback; kuwago is redirected', function () {
    $dates = ['start_date' => '2024-01-01', 'end_date' => '2024-12-31'];

    foreach (['owner', 'general', 'uddesign'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post('/uddesignfeedback/filter-by-date', $dates)
            ->assertOk()
            ->assertJsonStructure(['averageRating', 'ratingCounts']);
    }

    $this->actingAs(User::factory()->create(['role' => 'kuwago']))
        ->post('/uddesignfeedback/filter-by-date', $dates)
        ->assertRedirect('/kuwago-one');
});

it('redirects the uddesign role away from the Kuwago target-sale data', function () {
    $this->actingAs(User::factory()->create(['role' => 'uddesign']))
        ->get('/fetch-target-sale')
        ->assertRedirect('/uddesign');
});

it('lets only the owner choose the displayed target sale', function () {
    $target = TargetSales::factory()->create(['is_displayed' => false]);

    foreach (['general', 'kuwago', 'uddesign'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post("/target-sales/display/{$target->id}")
            ->assertRedirect('/');
    }
    expect((bool) $target->fresh()->is_displayed)->toBeFalse();

    $this->actingAs(User::factory()->create(['role' => 'owner']))
        ->post("/target-sales/display/{$target->id}");
    expect((bool) $target->fresh()->is_displayed)->toBeTrue();
});
