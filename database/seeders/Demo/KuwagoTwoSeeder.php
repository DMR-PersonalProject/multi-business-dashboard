<?php

namespace Database\Seeders\Demo;

class KuwagoTwoSeeder extends RestaurantSeeder
{
    protected function prefix(): string
    {
        return 'kuwago_two';
    }

    protected function scale(): float
    {
        return 0.75;
    }

    public static function menu(): array
    {
        return [
            'Rice Meals' => [['Chicken Teriyaki Bowl', 170, 8], ['Pork Tonkatsu', 185, 6], ['Garlic Butter Shrimp', 225, 3], ['Tocino Silog', 150, 7], ['Longganisa Silog', 150, 5]],
            'Pasta' => [['Truffle Cream Pasta', 210, 3], ['Aglio Olio', 160, 4], ['Baked Mac', 155, 4]],
            'Snacks' => [['Grilled Cheese Melt', 140, 4], ['Potato Wedges', 115, 5], ['Lumpiang Shanghai', 130, 6], ['Chicken Pops', 160, 3]],
            'Coffee' => [['Hot Brewed Coffee', 85, 6], ['Iced Latte', 130, 9], ['Salted Caramel Latte', 150, 6], ['Hazelnut Cold Brew', 155, 4]],
            'Non-Coffee' => [['Hojicha Latte', 145, 3], ['Mango Shake', 135, 5], ['Iced Lemon Tea', 90, 4]],
            'Desserts' => [['Halo-Halo', 145, 5], ['Biko Bites', 90, 2], ['Banana Turon a la Mode', 110, 3], ['Chocolate Lava Cake', 160, 1.5]],
        ];
    }
}
