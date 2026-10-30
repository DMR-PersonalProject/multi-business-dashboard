<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts, one per role.
     */
    public const USERS = [
        ['name' => 'Owner', 'email' => 'admin@example.com', 'role' => 'owner'],
        ['name' => 'General', 'email' => 'general@example.com', 'role' => 'general'],
        ['name' => 'Kuwago', 'email' => 'kuwago@example.com', 'role' => 'kuwago'],
        ['name' => 'Uddesign', 'email' => 'uddesign@example.com', 'role' => 'uddesign'],
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
            User::updateOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'role' => $user['role'], 'password' => $hash]
            );
        }

        if ($generated) {
            $this->command?->warn('SEED_USER_PASSWORD is not set. Demo password (shown once, copy it now): '.$password);
        } else {
            $this->command?->info('Demo users created with the password from SEED_USER_PASSWORD.');
        }
    }
}
