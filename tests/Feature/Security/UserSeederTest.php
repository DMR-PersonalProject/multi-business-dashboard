<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

function setSeedPassword(?string $value): void
{
    if ($value === null) {
        putenv('SEED_USER_PASSWORD');
        unset($_ENV['SEED_USER_PASSWORD'], $_SERVER['SEED_USER_PASSWORD']);
        return;
    }

    putenv("SEED_USER_PASSWORD={$value}");
    $_ENV['SEED_USER_PASSWORD'] = $_SERVER['SEED_USER_PASSWORD'] = $value;
}

afterEach(fn () => setSeedPassword(null));

it('uses SEED_USER_PASSWORD for every demo account', function () {
    setSeedPassword('a-long-local-demo-password');

    $this->seed(UserSeeder::class);

    expect(User::count())->toBe(4);
    foreach (User::all() as $user) {
        expect(Hash::check('a-long-local-demo-password', $user->password))->toBeTrue();
    }
    expect(User::pluck('role')->sort()->values()->all())->toBe(['general', 'kuwago', 'owner', 'uddesign']);
});

it('generates a random password and prints it once when none is set', function () {
    setSeedPassword(null);

    $this->artisan('db:seed', ['--class' => UserSeeder::class])
        ->expectsOutputToContain('shown once')
        ->assertSuccessful();

    $owner = User::where('role', 'owner')->first();
    expect($owner)->not->toBeNull();

    // Weak or well-known values must not work.
    foreach (['password', 'admin', 'owner', '12345678'] as $guess) {
        expect(Hash::check($guess, $owner->password))->toBeFalse();
    }
});

it('only uses example.com addresses', function () {
    setSeedPassword('a-long-local-demo-password');
    $this->seed(UserSeeder::class);

    foreach (User::pluck('email') as $email) {
        expect($email)->toEndWith('@example.com');
    }
});

it('can run twice without duplicating accounts', function () {
    setSeedPassword('a-long-local-demo-password');

    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::count())->toBe(4);
});

it('refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new UserSeeder)->run())->toThrow(RuntimeException::class);
    expect(User::count())->toBe(0);
});

it('is called by DatabaseSeeder', function () {
    setSeedPassword('a-long-local-demo-password');

    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'admin@example.com')->where('role', 'owner')->exists())->toBeTrue();
});
