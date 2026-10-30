<?php

namespace Database\Factories\KuwagoTwo;

use App\Models\KuwagoTwo\KuwagoTwoExpenseDetail;
use App\Models\KuwagoTwo\KuwagoTwoExpenseType;
use Database\Factories\Restaurant\ExpenseDetailFactory;

class KuwagoTwoExpenseDetailFactory extends ExpenseDetailFactory
{
    protected $model = KuwagoTwoExpenseDetail::class;

    protected string $typeModel = KuwagoTwoExpenseType::class;
}
