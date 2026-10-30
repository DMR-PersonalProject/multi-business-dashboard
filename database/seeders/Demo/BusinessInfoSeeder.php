<?php

namespace Database\Seeders\Demo;

use App\Models\BusinessInfo;

/**
 * Business profiles for the three businesses. Addresses are made up and the
 * images are placeholder files committed in public/.
 */
class BusinessInfoSeeder extends DemoSeeder
{
    protected function tables(): array
    {
        return ['business_infos'];
    }

    protected function seed(): void
    {
        $profiles = [
            ['Kuwago One', 'placeholder-kuwago-one.png', 2019, 'Restaurant', '101 Demo Street, Sample City',
                'Demo profile: all-day cafe and restaurant serving rice meals, pasta and coffee.'],
            ['Kuwago Two', 'placeholder-kuwago-two.png', 2022, 'Restaurant', '202 Placeholder Avenue, Sample City',
                'Demo profile: second Kuwago branch with a smaller menu and a dessert bar.'],
            ['UDDesign', '1730854574a single logo.png', 2020, 'Print and Merch Shop', '303 Example Road, Sample City',
                'Demo profile: printing, custom merchandise and made-to-order deals.'],
        ];

        foreach ($profiles as [$name, $image, $year, $type, $location, $description]) {
            BusinessInfo::factory()->create([
                'business_name' => $name,
                'business_logo' => '1730854574a single logo.png',
                'business_image' => $image,
                'year' => $year,
                'business_type' => $type,
                'location' => $location,
                'description' => $description,
            ]);
        }
    }
}
