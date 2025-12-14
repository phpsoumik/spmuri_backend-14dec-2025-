<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerFinancialController extends Controller
{
    public function getCustomerFinancialSummary(Request $request): JsonResponse
    {
        try {
            $customers = Customer::where('status', 'true')
                ->with(['saleInvoice'])
                ->orderBy('username', 'asc')
                ->get();

            $financialData = $customers->map(function ($customer) {
                $invoiceIds = $customer->saleInvoice->pluck('id');

                if ($invoiceIds->isEmpty()) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->username,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'totalPurchase' => 0,
                        'totalPaid' => 0,
                        'totalDue' => 0,
                        'totalOrders' => 0
                    ];
                }

                // Total purchase amount
                $totalPurchase = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $invoiceIds)
                    ->where('debitId', 4)
                    ->sum('amount');

                // Total paid amount  
                $totalPaid = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $invoiceIds)
                    ->where('creditId', 4)
                    ->sum('amount');

                // Return amounts
                $returnAmount = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $invoiceIds)
                    ->where('creditId', 4)
                    ->sum('amount');

                $instantReturn = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $invoiceIds)
                    ->where('debitId', 4)
                    ->sum('amount');

                $totalDue = (($totalPurchase - $returnAmount) - $totalPaid) + $instantReturn;

                return [
                    'id' => $customer->id,
                    'name' => $customer->username,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'totalPurchase' => round($totalPurchase, 2),
                    'totalPaid' => round($totalPaid, 2),
                    'totalDue' => round($totalDue, 2),
                    'totalOrders' => $invoiceIds->count()
                ];
            });

            $summary = [
                'totalCustomers' => $financialData->count(),
                'totalPurchaseAmount' => round($financialData->sum('totalPurchase'), 2),
                'totalPaidAmount' => round($financialData->sum('totalPaid'), 2),
                'totalDueAmount' => round($financialData->sum('totalDue'), 2)
            ];

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'customers' => $financialData->values()
            ], 200);

        } catch (Exception $err) {
            return response()->json([
                'success' => false,
                'error' => $err->getMessage()
            ], 500);
        }
    }
}