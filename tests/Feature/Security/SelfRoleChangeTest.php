<?php

use App\Models\User;

function settingsPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Updated name',
        'description' => 'Updated description',
        'first_name' => 'First',
        'last_name' => 'Last',
        'initial' => 'M',
        'email' => 'kuwago.user@example.com',
        'phone_number' => '0000000000',
    ], $overrides);
}

it('ignores a role sent to the own-settings form', function () {
    $user = User::factory()->create([
        'role' => 'kuwago',
        'email' => 'kuwago.user@example.com',
        'user_image' => 'default_user_image.png',
    ]);

    $this->actingAs($user)
        ->put(route('settings.account-update', $user->id), settingsPayload(['role' => 'owner']))
        ->assertRedirect(route('settings.account-show', $user->id));

    $user->refresh();
    expect($user->role)->toBe('kuwago')
        ->and($user->name)->toBe('Updated name');
});

it('still saves own settings without a role field', function () {
    $user = User::factory()->create([
        'role' => 'general',
        'email' => 'kuwago.user@example.com',
        'user_image' => 'default_user_image.png',
    ]);

    $this->actingAs($user)
        ->put(route('settings.account-update', $user->id), settingsPayload())
        ->assertSessionHasNoErrors();

    expect($user->refresh()->role)->toBe('general');
});

it('does not render an editable role field on the own-settings page', function () {
    $user = User::factory()->create(['role' => 'kuwago', 'user_image' => 'default_user_image.png']);

    $this->actingAs($user)
        ->get(route('settings.account-edit', $user->id))
        ->assertOk()
        ->assertDontSee('name="role"', false);
});
