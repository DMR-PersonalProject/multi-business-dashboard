<?php

use App\Models\User;

// No Google service mocks on purpose: list pages must work without the
// service-account key files.

beforeEach(function () {
    $this->owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($this->owner);
});

dataset('pages', [
    '/kuwago-one', '/kuwago-one/sales', '/kuwago-one/expenses', '/kuwago-one/promos', '/kuwago-one/feedbacks',
    '/kuwago-two', '/kuwago-two/sales', '/kuwago-two/expenses', '/kuwago-two/promos', '/kuwago-two/feedbacks',
    '/uddesign', '/uddesign/sales', '/uddesign/expenses', '/uddesign/uddeals', '/uddesign/feedbacks',
    '/kuwago-one?interval=this_month', '/kuwago-two?interval=this_month', '/uddesign?interval=this_month',
    '/executive', '/executive/weather', '/fetch-target-sale',
    '/deals', '/feedback', '/account', '/promos', '/targetSales', '/business',
]);

it('opens every page on an empty database', function (string $uri) {
    $this->get($uri)->assertOk();
})->with('pages');

it('opens the settings page on an empty database', function () {
    $this->get("/settings/{$this->owner->id}")->assertOk();
});

it('shows an empty target gauge when no target sale is displayed', function (string $uri) {
    $this->get($uri)
        ->assertOk()
        ->assertSee('var targetSaleAmount = 0;', false);
})->with(['/kuwago-one', '/fetch-target-sale']);

it('sends the old UDDesign feedback page to the working one', function () {
    $this->get('/uddesignfeedback')->assertRedirect('/uddesign/feedbacks');
});
