<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts, one per role. Profile details are fake (0900-555 numbers).
     */
    public const USERS = [
        ['name' => 'Owner', 'email' => 'admin@example.com', 'role' => 'owner', 'first_name' => 'Demo', 'last_name' => 'Owner', 'phone_number' => '0900-555-0001', 'description' => 'Demo owner account'],
        ['name' => 'General', 'email' => 'general@example.com', 'role' => 'general', 'first_name' => 'Demo', 'last_name' => 'Finance', 'phone_number' => '0900-555-0002', 'description' => 'Demo finance officer account'],
        ['name' => 'Kuwago', 'email' => 'kuwago@example.com', 'role' => 'kuwago', 'first_name' => 'Demo', 'last_name' => 'Kuwago', 'phone_number' => '0900-555-0003', 'description' => 'Demo Kuwago manager account'],
        ['name' => 'Uddesign', 'email' => 'uddesign@example.com', 'role' => 'uddesign', 'first_name' => 'Demo', 'last_name' => 'Uddesign', 'phone_number' => '0900-555-0004', 'description' => 'Demo UDDesign manager account'],
    ];

    /**
     * Run the database seeds.
     *
     * The demo password comes from SEED_USER_PASSWORD. If that is empty, a random
     * password is generated and printed once; it is not stored anywhere else.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('UserSeeder creates demo accounts and must not run in production.');
        }

        $password = (string) env('SEED_USER_PASSWORD', '');
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16, symbols: false);
        }

        $hash = Hash::make($password);

        foreach (self::USERS as $user) {
            $model = User::updateOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'role' => $user['role'], 'password' => $hash]
            );

            // Fill the fake profile only where empty, so edits survive a password reseed
            $profile = Arr::except($user, ['name', 'email', 'role']) + ['initial' => 'D'];
            foreach ($profile as $field => $value) {
                $model->$field ??= $value;
            }
            $model->save();
        }

        if ($generated) {
            $this->command?->warn('SEED_USER_PASSWORD is not set. Demo password (shown once, copy it now): '.$password);
        } else {
            $this->command?->info('Demo users created with the password from SEED_USER_PASSWORD.');
        }
    }
}
