<?php

namespace Database\Factories\KuwagoOne;

use App\Models\KuwagoOne\KuwagoOneOrderDetails;
use App\Models\KuwagoOne\KuwagoOneDishes;
use Database\Factories\Restaurant\OrderDetailFactory;

class KuwagoOneOrderDetailsFactory extends OrderDetailFactory
{
    protected $model = KuwagoOneOrderDetails::class;

    protected string $dishModel = KuwagoOneDishes::class;
}
