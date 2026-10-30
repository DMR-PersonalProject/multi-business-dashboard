<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\UddesignReport;
use Illuminate\Support\Carbon;
use App\Models\KuwagoOneReport;
use App\Models\KuwagoTwoReport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;
use App\Models\KuwagoOne\KuwagoOneDishes;
use App\Models\KuwagoTwo\KuwagoTwoDishes;
use App\Models\Uddesign\UddesignMerchType;
use App\Models\Uddesign\UddesignPrintType;
use App\Models\KuwagoOne\KuwagoOneCategory;
use App\Models\KuwagoTwo\KuwagoTwoCategory;
use App\Models\Uddesign\UddesignExpenseType;
use App\Models\Uddesign\UddesignMerchDetail;
use App\Models\Uddesign\UddesignPrintDetail;
use App\Models\KuwagoOne\KuwagoOneExpenseType;
use App\Models\KuwagoTwo\KuwagoTwoExpenseType;
use App\Models\Uddesign\UddesignExpenseDetail;
use App\Models\Uddesign\UddesignMerchCategory;
use App\Models\Uddesign\UddesignPrintCategory;
use App\Models\KuwagoOne\KuwagoOneOrderDetails;
use App\Models\KuwagoTwo\KuwagoTwoOrderDetails;
use App\Models\KuwagoOne\KuwagoOneExpenseDetail;
use App\Models\KuwagoTwo\KuwagoTwoExpenseDetail;
use App\Models\Uddesign\UddesignExpenseCategory;
use App\Models\KuwagoOne\KuwagoOneExpenseCategory;
use App\Models\KuwagoTwo\KuwagoTwoExpenseCategory;

class DataController extends Controller
{
    public const TYPES = ['uddesign', 'kuwago_one', 'kuwago_two'];

    private const BATCH_SIZE = 1000;

    // UNIVERSAL REFRESH BUTTON HANDLER
    // Fetches and checks every endpoint first; only if all succeed are the
    // local tables replaced, inside one transaction.
    public function refreshData(Request $request, string $type)
    {
        if (!in_array($type, self::TYPES, true)) {
            return back()->with('failed', 'Invalid type specified.');
        }

        $source = $this->source($type);

        $apiToken = $request->session()->get($source['token_key']);
        if (!$apiToken) {
            return back()->with('failed', 'API token not found in session. Log out and log in again, then retry.');
        }

        if (blank($source['base_url'])) {
            return back()->with('failed', 'The API URL for this business is not configured.');
        }

        try {
            $rowsByModel = [];
            foreach ($source['tables'] as [$endpoint, $model, $mapRow]) {
                $rowsByModel[$model] = $this->fetchRows($apiToken, $source['base_url'], $endpoint, $mapRow);
            }
        } catch (UnexpectedValueException $e) {
            return back()->with('failed', $e->getMessage().' No data was changed.');
        }

        DB::transaction(function () use ($rowsByModel) {
            foreach ($rowsByModel as $model => $rows) {
                $model::query()->delete();

                foreach (array_chunk($rows, self::BATCH_SIZE) as $batch) {
                    $model::insert($batch);
                }
            }
        });

        return back()->with('status', 'Data refreshed successfully!');
    }

    /**
     * Token session key, base URL and the endpoints to load for one business.
     * Each table entry is [endpoint, model class, row mapper].
     */
    private function source(string $type): array
    {
        return match ($type) {
            'uddesign' => [
                'token_key' => 'uddesign_api_token',
                'base_url' => env('UDDESIGN_API_URL', ''),
                'tables' => [
                    ['/api/data', UddesignReport::class, function ($report) {
                        return [
                            'cash' => $report['cash'],
                            'gcash' => $report['gcash'],
                            'print_sales' => $report['print_sales'],
                            'merch_sales' => $report['merch_sales'],
                            'custom_sales' => $report['custom_sales'],
                            'total_sales' => $report['total_sales'],
                            'print_expenses' => $report['print_expenses'],
                            'merch_expenses' => $report['merch_expenses'],
                            'custom_expenses' => $report['custom_expenses'],
                            'total_expenses' => $report['total_expenses'],
                            'date' => Carbon::parse($report['date'])->format('Y-m-d H:i:s'),
                        ];
                    }],
                    ['/api/merch-category', UddesignMerchCategory::class, function ($merchCategory) {
                        return [
                            'merch_category_id' => $merchCategory['id'],
                            'name' => $merchCategory['name'],
                        ];
                    }],
                    ['/api/merch-type', UddesignMerchType::class, function ($merchType) {
                        return [
                            'merch_type_id' => $merchType['id'],
                            'merch_category_id' => $merchType['merch_category_id'],
                            'name' => $merchType['name'],
                        ];
                    }],
                    ['/api/print-category', UddesignPrintCategory::class, function ($printCategory) {
                        return [
                            'print_category_id' => $printCategory['id'],
                            'name' => $printCategory['name'],
                        ];
                    }],
                    ['/api/print-type', UddesignPrintType::class, function ($printType) {
                        return [
                            'print_type_id' => $printType['id'],
                            'print_category_id' => $printType['print_category_id'],
                            'name' => $printType['name'],
                        ];
                    }],
                    ['/api/expense-category', UddesignExpenseCategory::class, function ($expenseCategory) {
                        return [
                            'expense_category_id' => $expenseCategory['id'],
                            'name' => $expenseCategory['name'],
                        ];
                    }],
                    ['/api/expense-type', UddesignExpenseType::class, function ($expenseType) {
                        return [
                            'expense_type_id' => $expenseType['id'],
                            'expense_category_id' => $expenseType['expense_category_id'],
                            'name' => $expenseType['name'],
                        ];
                    }],
                    ['/api/merch-details', UddesignMerchDetail::class, function ($detail) {
                        return [
                            'merch_type_id' => $detail['merch_type_id'],
                            'pcs' => $detail['pcs'],
                            'date' => Carbon::parse($detail['date'])->format('Y-m-d H:i:s'),
                        ];
                    }],
                    ['/api/print-details', UddesignPrintDetail::class, function ($detail) {
                        return [
                            'print_type_id' => $detail['print_type_id'],
                            'pcs' => $detail['pcs'],
                            'date' => Carbon::parse($detail['date'])->format('Y-m-d H:i:s'),
                        ];
                    }],
                    ['/api/expense-details', UddesignExpenseDetail::class, $this->mapExpenseDetail(...)],
                ],
            ],
            'kuwago_one' => [
                'token_key' => 'kuwago_one_api_token',
                'base_url' => env('KUWAGO_ONE_API_URL', ''),
                'tables' => [
                    ['/api/reports', KuwagoOneReport::class, function ($report) {
                        return [
                            'cash' => $report['cash'],
                            'gcash' => $report['gcash'],
                            'sales' => $report['total_remittance'],
                            'date' => $report['date'],
                        ];
                    }],
                    ['/api/categories', KuwagoOneCategory::class, $this->mapDishCategory(...)],
                    ['/api/dishes', KuwagoOneDishes::class, $this->mapDish(...)],
                    ['/api/expense-category', KuwagoOneExpenseCategory::class, $this->mapExpenseCategory(...)],
                    ['/api/expense-type', KuwagoOneExpenseType::class, $this->mapExpenseType(...)],
                    ['/api/order-details', KuwagoOneOrderDetails::class, $this->mapOrderDetail(...)],
                    ['/api/expense-details', KuwagoOneExpenseDetail::class, $this->mapExpenseDetail(...)],
                ],
            ],
            'kuwago_two' => [
                'token_key' => 'kuwago_two_api_token',
                'base_url' => env('KUWAGO_TWO_API_URL', ''),
                'tables' => [
                    ['/api/reports', KuwagoTwoReport::class, function ($report) {
                        return [
                            'orders' => $report['orders'],
                            'cash' => $report['cash'],
                            'gcash' => $report['gcash'],
                            'sales' => $report['sales'],
                            'expenses' => $report['expenses'],
                            'date' => $report['date'],
                        ];
                    }],
                    ['/api/categories', KuwagoTwoCategory::class, $this->mapDishCategory(...)],
                    ['/api/dishes', KuwagoTwoDishes::class, $this->mapDish(...)],
                    ['/api/expense-category', KuwagoTwoExpenseCategory::class, $this->mapExpenseCategory(...)],
                    ['/api/expense-type', KuwagoTwoExpenseType::class, $this->mapExpenseType(...)],
                    ['/api/order-details', KuwagoTwoOrderDetails::class, $this->mapOrderDetail(...)],
                    ['/api/expense-details', KuwagoTwoExpenseDetail::class, $this->mapExpenseDetail(...)],
                ],
            ],
        };
    }

    private function mapDishCategory($dishCategory): array
    {
        return [
            'category_id' => $dishCategory['id'],
            'name' => $dishCategory['name'],
        ];
    }

    private function mapDish($dishType): array
    {
        return [
            'dish_id' => $dishType['id'],
            'category_id' => $dishType['category_id'],
            'name' => $dishType['name'],
        ];
    }

    private function mapExpenseCategory($expenseCategory): array
    {
        return [
            'expense_category_id' => $expenseCategory['id'],
            'name' => $expenseCategory['name'],
        ];
    }

    private function mapExpenseType($expenseType): array
    {
        return [
            'expense_type_id' => $expenseType['id'],
            'expense_category_id' => $expenseType['expense_category_id'],
            'name' => $expenseType['name'],
        ];
    }

    private function mapOrderDetail($orderDetail): array
    {
        return [
            'dish_id' => $orderDetail['dish_id'],
            'pcs' => $orderDetail['pcs'],
            'date' => Carbon::parse($orderDetail['date'])->format('Y-m-d H:i:s'),
        ];
    }

    private function mapExpenseDetail($expenseDetail): array
    {
        return [
            'expense_type_id' => $expenseDetail['expense_type_id'],
            'price' => $expenseDetail['price'],
            'date' => Carbon::parse($expenseDetail['date'])->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Fetch one endpoint and map every row. Throws if the request fails or the
     * response is not a {"data": [ {...}, ... ]} list with the expected fields.
     */
    private function fetchRows(string $apiToken, string $baseUrl, string $endpoint, callable $mapRow): array
    {
        try {
            $response = Http::withToken($apiToken)->acceptJson()->get($baseUrl.$endpoint);
        } catch (\Throwable $e) {
            Log::warning("Data refresh: request to {$endpoint} failed: ".$e->getMessage());
            throw new UnexpectedValueException("Could not reach the API ({$endpoint}).");
        }

        if (!$response->successful()) {
            throw new UnexpectedValueException("The API returned an error for {$endpoint}.");
        }

        $data = $response->json('data');
        if (!is_array($data) || !array_is_list($data)) {
            throw new UnexpectedValueException("Unexpected response format from {$endpoint}.");
        }

        $rows = [];
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new UnexpectedValueException("Unexpected row {$index} from {$endpoint}.");
            }

            try {
                $rows[] = $mapRow($item);
            } catch (\Throwable $e) {
                throw new UnexpectedValueException("Missing or invalid fields in row {$index} from {$endpoint}.");
            }
        }

        return $rows;
    }
}
