<?php

namespace Database\Factories\KuwagoOne;

use App\Models\KuwagoOne\KuwagoOneDishes;
use App\Models\KuwagoOne\KuwagoOneCategory;
use Database\Factories\Restaurant\MenuDishFactory;

class KuwagoOneDishesFactory extends MenuDishFactory
{
    protected $model = KuwagoOneDishes::class;

    protected string $categoryModel = KuwagoOneCategory::class;
}
