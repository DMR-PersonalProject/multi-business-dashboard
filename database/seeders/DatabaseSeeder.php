<?php

namespace Database\Seeders;

use Database\Seeders\Demo;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the demo accounts and about 12 months of fake data ending today.
     * Everything here is invented for demos; nothing comes from the business
     * apps, Google Sheets or any other external source.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DatabaseSeeder creates demo accounts and fake data and must not run in production.');
        }

        // Order matters: promos read the Kuwago reports, UDDesign custom sales
        // come from closed deals, and targets are based on all the reports.
        $this->call([
            UserSeeder::class,
            Demo\BusinessInfoSeeder::class,
            Demo\KuwagoOneSeeder::class,
            Demo\KuwagoTwoSeeder::class,
            Demo\DealSeeder::class,
            Demo\UddesignSeeder::class,
            Demo\FeedbackSeeder::class,
            Demo\PromoSeeder::class,
            Demo\TargetSalesSeeder::class,
        ]);
    }
}
