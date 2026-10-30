<?php

namespace Database\Factories\KuwagoOne;

use App\Models\KuwagoOne\KuwagoOneExpenseType;
use App\Models\KuwagoOne\KuwagoOneExpenseCategory;
use Database\Factories\Restaurant\ExpenseTypeFactory;

class KuwagoOneExpenseTypeFactory extends ExpenseTypeFactory
{
    protected $model = KuwagoOneExpenseType::class;

    protected string $categoryModel = KuwagoOneExpenseCategory::class;
}
