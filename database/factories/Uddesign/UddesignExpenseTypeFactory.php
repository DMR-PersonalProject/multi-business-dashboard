<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignExpenseType;
use App\Models\Uddesign\UddesignExpenseCategory;
use Database\Factories\Restaurant\ExpenseTypeFactory;

class UddesignExpenseTypeFactory extends ExpenseTypeFactory
{
    protected $model = UddesignExpenseType::class;

    protected string $categoryModel = UddesignExpenseCategory::class;
}
