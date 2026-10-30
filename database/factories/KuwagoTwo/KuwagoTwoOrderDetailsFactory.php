<?php

namespace Database\Factories\KuwagoTwo;

use App\Models\KuwagoTwo\KuwagoTwoOrderDetails;
use App\Models\KuwagoTwo\KuwagoTwoDishes;
use Database\Factories\Restaurant\OrderDetailFactory;

class KuwagoTwoOrderDetailsFactory extends OrderDetailFactory
{
    protected $model = KuwagoTwoOrderDetails::class;

    protected string $dishModel = KuwagoTwoDishes::class;
}
