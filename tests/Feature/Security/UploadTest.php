<?php

use App\Models\BusinessInfo;
use App\Models\User;
use App\Support\ImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(ImageUpload::DISK);
});

/**
 * A real (non-fake) upload, so the MIME type is detected from the content.
 */
function realUpload(string $originalName, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($path, $content);

    return new UploadedFile($path, $originalName, null, null, true);
}

function pngBytes(): string
{
    $image = imagecreatetruecolor(4, 4);
    ob_start();
    imagepng($image);
    return ob_get_clean();
}

function ownSettingsPayload(User $user, array $extra = []): array
{
    return array_merge([
        'name' => 'Name', 'description' => 'Description', 'first_name' => 'First',
        'last_name' => 'Last', 'initial' => 'M', 'email' => $user->email, 'phone_number' => '0000000000',
    ], $extra);
}

it('stores a valid picture under a random name with an extension from its content', function () {
    $user = User::factory()->create(['role' => 'kuwago', 'user_image' => 'default_user_image.png']);

    $this->actingAs($user)
        ->put(route('settings.account-update', $user->id), ownSettingsPayload($user, [
            'user_image' => realUpload('holiday photo.jpeg', pngBytes()),
        ]))
        ->assertSessionHasNoErrors();

    $stored = $user->refresh()->user_image;

    expect($stored)->toMatch('/^[A-Za-z0-9]{40}\.png$/')
        ->and($stored)->not->toContain('holiday');
    Storage::disk(ImageUpload::DISK)->assertExists("user_images/{$stored}");
});

it('rejects files that are not raster images', function (string $name, string $content) {
    $user = User::factory()->create(['role' => 'kuwago', 'user_image' => 'default_user_image.png']);

    $this->actingAs($user)
        ->put(route('settings.account-update', $user->id), ownSettingsPayload($user, [
            'user_image' => realUpload($name, $content),
        ]))
        ->assertSessionHasErrors('user_image');

    expect($user->refresh()->user_image)->toBe('default_user_image.png');
    expect(Storage::disk(ImageUpload::DISK)->allFiles())->toBe([]);
})->with([
    'text renamed to png' => ['notes.png', 'just some text'],
    'php script' => ['shell.php', '<?php echo 1;'],
    'real png with a php name' => ['evil.php', pngBytes()],
    'svg' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
]);

it('rejects images over 2 MB', function () {
    $user = User::factory()->create(['role' => 'kuwago', 'user_image' => 'default_user_image.png']);

    $this->actingAs($user)
        ->put(route('settings.account-update', $user->id), ownSettingsPayload($user, [
            'user_image' => UploadedFile::fake()->image('big.jpg')->size(ImageUpload::MAX_KILOBYTES + 1),
        ]))
        ->assertSessionHasErrors('user_image');

    expect(Storage::disk(ImageUpload::DISK)->allFiles())->toBe([]);
});

it('stores business images and logos under random names', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)->post('/business', [
        'business_name' => 'Kuwago One',
        'business_image' => realUpload('../../cafe photo.png', pngBytes()),
        'business_logo' => UploadedFile::fake()->image('logo.jpg'),
        'year' => 2020,
        'business_type' => 'Cafe',
        'location' => 'Town',
        'description' => 'Coffee',
    ])->assertSessionHasNoErrors();

    $business = BusinessInfo::first();

    expect($business->business_image)->toMatch('/^[A-Za-z0-9]{40}\.png$/')
        ->and($business->business_logo)->toMatch('/^[A-Za-z0-9]{40}\.jpg$/');
    Storage::disk(ImageUpload::DISK)->assertExists("business_images/{$business->business_image}");
    Storage::disk(ImageUpload::DISK)->assertExists("business_logos/{$business->business_logo}");
});

it('stores pictures uploaded by the owner in account management under random names', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $user = User::factory()->create(['role' => 'general', 'user_image' => 'default_user_image.png']);

    $this->actingAs($owner)->put("/account/{$user->id}", ownSettingsPayload($user, [
        'role' => 'general',
        'user_image' => UploadedFile::fake()->image('portrait.webp'),
    ]))->assertSessionHasNoErrors();

    expect($user->refresh()->user_image)->toMatch('/^[A-Za-z0-9]{40}\.webp$/');
});

it('keeps the bundled default, placeholder and logo images', function (string $path) {
    expect(file_exists(public_path($path)))->toBeTrue();
})->with([
    'user_images/default_user_image.png',
    'assets/images/icons/user-profile-img.png',
    'business_images/placeholder-kuwago-one.png',
    'business_images/placeholder-kuwago-two.png',
    'business_images/1730854574a single logo.png',
    'user_images/1730856351a single logo.png',
]);

it('still shows the default avatar for users without a picture', function () {
    $user = User::factory()->create(['role' => 'kuwago', 'user_image' => null]);

    $this->actingAs($user)
        ->get(route('settings.account-show', $user->id))
        ->assertOk()
        ->assertSee('user_images/default_user_image.png', false);
});
