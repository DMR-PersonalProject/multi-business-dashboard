<?php

namespace Database\Factories\KuwagoTwo;

use App\Models\KuwagoTwo\KuwagoTwoDishes;
use App\Models\KuwagoTwo\KuwagoTwoCategory;
use Database\Factories\Restaurant\MenuDishFactory;

class KuwagoTwoDishesFactory extends MenuDishFactory
{
    protected $model = KuwagoTwoDishes::class;

    protected string $categoryModel = KuwagoTwoCategory::class;
}
