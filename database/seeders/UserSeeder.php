<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Owner',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin'),
            'role' => 'owner'
        ]);

        User::create([
            'name' => 'General',
            'email' => 'general@example.com',
            'password' => Hash::make('admin'),
            'role' => 'general'
        ]);

        User::create([
            'name' => 'Kuwago',
            'email' => 'kuwago@example.com',
            'password' => Hash::make('admin'),
            'role' => 'kuwago'
        ]);

        User::create([
            'name' => 'Uddesign',
            'email' => 'uddesign@example.com',
            'password' => Hash::make('admin'),
            'role' => 'uddesign'
        ]);
    }
}
