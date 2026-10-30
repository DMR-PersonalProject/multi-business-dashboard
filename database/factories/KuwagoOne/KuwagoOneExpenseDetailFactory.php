<?php

namespace Database\Factories\KuwagoOne;

use App\Models\KuwagoOne\KuwagoOneExpenseDetail;
use App\Models\KuwagoOne\KuwagoOneExpenseType;
use Database\Factories\Restaurant\ExpenseDetailFactory;

class KuwagoOneExpenseDetailFactory extends ExpenseDetailFactory
{
    protected $model = KuwagoOneExpenseDetail::class;

    protected string $typeModel = KuwagoOneExpenseType::class;
}
