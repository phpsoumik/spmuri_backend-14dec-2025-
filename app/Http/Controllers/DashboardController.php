<?php

namespace App\Http\Controllers;
//

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\SaleInvoice;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            $appData = AppSetting::first();
            if (!$appData) {
                return response()->json(['error' => 'App settings not found'], 404);
            }
            if (!in_array($appData->dashboardType, ['inventory', 'e-commerce', 'both'])) {
                return response()->json(['error' => 'Invalid dashboard type'], 400);
            }

            if ($appData->dashboardType === 'inventory') {

                $allSaleInvoices = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                    ->get();

                // $totalSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                //     return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                //         ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                // })
                //     ->groupBy('date')
                //     ->orderBy('date', 'desc')
                //     ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount,SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                //     ->count();

                $totalSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })->count();

                $allPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount,SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                    ->get();

                // $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                //     return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                //         ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                // })
                //     ->groupBy('date')
                //     ->orderBy('date', 'desc')
                //     ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount,SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                //     ->count();

                $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                });

                $totalPurchaseInvoice = $totalPurchaseInvoice->count();


                //total sale and total purchase amount is calculated by subtracting total discount from total amount (saiyed)
                $cartInfo = [
                    'totalSaleInvoice' => $totalSaleInvoice,
                    'totalSaleAmount' => $allSaleInvoices->sum('totalAmount'),
                    'totalSaleDue' => $allSaleInvoices->sum('dueAmount'),
                    'totalPurchaseInvoice' => $totalPurchaseInvoice,
                    'totalPurchaseAmount' => $allPurchaseInvoice->sum('totalAmount'),
                    'totalPurchaseDue' => $allPurchaseInvoice->sum('dueAmount')
                ];
                return response()->json($cartInfo, 200);

            } else if ($appData->dashboardType === 'both') {

                $allSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                    ->get();

                $allPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                    ->get();

                // $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                //     return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                //         ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                // })
                //     ->groupBy('date')
                //     ->orderBy('date', 'desc')
                //     ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                //     ->count();

                $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')));
                })->count();


                $totalSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                    ->count();


                $cardInfo = [
                    'totalPurchaseInvoice' => $totalPurchaseInvoice,
                    'totalPurchaseAmount' => $allPurchaseInvoice->sum('totalAmount'),
                    'totalPurchaseDue' => $allPurchaseInvoice->sum('dueAmount'),
                ];

                return response()->json($cardInfo, 200);
            } else {
                return response()->json(['error' => 'Invalid dashboard type'], 400);
            }
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }
}
