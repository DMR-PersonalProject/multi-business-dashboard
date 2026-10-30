<?php

namespace Database\Factories\Uddesign;

use App\Models\Uddesign\UddesignExpenseDetail;
use App\Models\Uddesign\UddesignExpenseType;
use Database\Factories\Restaurant\ExpenseDetailFactory;

class UddesignExpenseDetailFactory extends ExpenseDetailFactory
{
    protected $model = UddesignExpenseDetail::class;

    protected string $typeModel = UddesignExpenseType::class;
}
