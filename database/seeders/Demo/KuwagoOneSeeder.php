<?php

namespace Database\Seeders\Demo;

class KuwagoOneSeeder extends RestaurantSeeder
{
    protected function prefix(): string
    {
        return 'kuwago_one';
    }

    protected function scale(): float
    {
        return 1.0;
    }

    public static function menu(): array
    {
        return [
            'Rice Meals' => [['Chicken Adobo Bowl', 165, 9], ['Pork Sisig Rice', 175, 8], ['Beef Tapa', 185, 6], ['Chicken Inasal', 170, 5], ['Bangus Belly', 195, 3]],
            'Pasta' => [['Carbonara', 180, 6], ['Pesto Pasta', 175, 3], ['Spaghetti Bolognese', 160, 4], ['Seafood Marinara', 220, 1.5]],
            'Snacks' => [['Clubhouse Sandwich', 165, 4], ['Nachos Overload', 150, 3], ['Fries Basket', 110, 5], ['Chicken Wings (6 pcs)', 210, 3]],
            'Coffee' => [['Americano', 95, 6], ['Cafe Latte', 125, 8], ['Spanish Latte', 140, 9], ['Caramel Macchiato', 150, 5], ['Iced Mocha', 145, 4]],
            'Non-Coffee' => [['Matcha Latte', 140, 4], ['Iced Chocolate', 120, 3], ['Calamansi Juice', 85, 3], ['Strawberry Milk', 130, 2]],
            'Desserts' => [['Mango Float', 120, 4], ['Ube Cheesecake', 150, 2.5], ['Leche Flan', 95, 2], ['Brownie a la Mode', 135, 1]],
        ];
    }
}
