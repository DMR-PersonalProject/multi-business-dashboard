<?php

namespace Database\Factories\KuwagoTwo;

use App\Models\KuwagoTwo\KuwagoTwoExpenseType;
use App\Models\KuwagoTwo\KuwagoTwoExpenseCategory;
use Database\Factories\Restaurant\ExpenseTypeFactory;

class KuwagoTwoExpenseTypeFactory extends ExpenseTypeFactory
{
    protected $model = KuwagoTwoExpenseType::class;

    protected string $categoryModel = KuwagoTwoExpenseCategory::class;
}
