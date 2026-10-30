<?php

use App\Models\Feedback;
use App\Models\UddesignFeedback;
use App\Models\User;
use App\Services\GoogleSheetService;
use App\Services\UdGoogleSheetsService;

const SCRIPT_TEXT = '<script>alert("feedback")</script>';

beforeEach(function () {
    app()->instance(GoogleSheetService::class, Mockery::mock(GoogleSheetService::class));
    app()->instance(UdGoogleSheetsService::class, Mockery::mock(UdGoogleSheetsService::class));

    Feedback::create([
        'name' => SCRIPT_TEXT, 'feedback_type' => 'Comment', 'dish' => SCRIPT_TEXT,
        'comments' => SCRIPT_TEXT, 'rating' => 5, 'feedback_date' => now()->toDateString(),
    ]);
    UddesignFeedback::create([
        'name' => SCRIPT_TEXT, 'feedback_type' => 'Comment', 'product_name' => SCRIPT_TEXT,
        'comments' => SCRIPT_TEXT, 'rating' => 5, 'feedback_date' => now()->toDateString(),
    ]);
});

it('shows script text in feedback as text on the feedback pages', function (string $url) {
    $this->actingAs(User::factory()->create(['role' => 'owner']))
        ->get($url)
        ->assertOk()
        ->assertDontSee(SCRIPT_TEXT, false)
        ->assertSee(e(SCRIPT_TEXT), false);
})->with([
    '/kuwago-one/feedbacks',
    '/kuwago-two/feedbacks',
    '/uddesign/feedbacks',
    '/feedback',
]);

it('escapes feedback fields in the date-filter script of every feedback view', function (string $view, string $productField) {
    $source = file_get_contents(resource_path("views/{$view}"));

    expect($source)->toContain('function escapeHtml(value)');

    foreach (['name', $productField, 'comments'] as $field) {
        expect($source)
            ->toContain('${escapeHtml(item.'.$field.')}')
            ->not->toContain('${item.'.$field.'}');
    }
})->with([
    ['general/kuwago-one/feedbacks-content.blade.php', 'dish'],
    ['general/kuwago-two/feedbacks-content.blade.php', 'dish'],
    ['general/uddesign/feedbacks-content.blade.php', 'dish'],
    ['feedback/index.blade.php', 'dish'],
    ['uddesignfeedback/index.blade.php', 'product_name'],
]);
