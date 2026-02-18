<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SaleInvoice;
use App\Models\Transaction;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentSaleInvoiceController extends Controller
{
    //create paymentSaleInvoice controller method
    public function createSinglePaymentSaleInvoice(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $date = $request->input('date');
            
            $transaction = [];
            $amount = 0;

            // get single Sale invoice 
            $saleInvoice = SaleInvoice::where('id', $request->input('saleInvoiceNo'))
                ->first();

            if (!$saleInvoice) {
                return response()->json(['error' => 'Invoice not Found'], 404);
            }

            // transaction of the total amount
            $totalAmount = Transaction::where('type', 'sale')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the paidAmount
            $totalPaidAmount = Transaction::where('type', 'sale')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where(function ($query) {
                    $query->orWhere('creditId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the total amount
            $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where(function ($query) {
                    $query->where('creditId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the total instant return
            $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // calculation of due amount
            $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

            foreach ($request->paidAmount as $amountData) {
                $amount += $amountData['amount'];
            }

            // Get customer current due for validation (includes commission and previous due)
            $customer = \App\Models\Customer::find($saleInvoice->customerId);
            $customerCurrentDue = $customer ? $customer->current_due_amount : 0;

            // Calculate total due including commission and bag amount
            $invoiceDue = $saleInvoice->dueAmount ?? 0;
            $commissionValue = $saleInvoice->commission_value ?? 0;
            $bagAmount = ($saleInvoice->bag_quantity ?? 0) * ($saleInvoice->bag_price ?? 0);
            $previousDue = $saleInvoice->customer_previous_due ?? 0;
            $totalDueWithCommission = $invoiceDue + $commissionValue + $bagAmount + $previousDue;

            // validation with total due including commission and bag amount
            if ($amount > $totalDueWithCommission) {
                return response()->json(['error' => 'Amount cannot be greater than total due (Rs ' . number_format($totalDueWithCommission, 2) . ')!'], 400);
            }

            // new transactions will be created as journal entry for paid amount
            foreach ($request->paidAmount as $amountData) {
                if ($amountData['amount'] > 0) {
                    $transaction = Transaction::create([
                        'date' => $date,
                        'debitId' => $amountData['paymentType'] ? $amountData['paymentType'] : 1,
                        'creditId' => 4,
                        'amount' => takeUptoThreeDecimal($amountData['amount']),
                        'particulars' => "Received payment due of Sale Invoice #{$request->input('saleInvoiceNo')}",
                        'type' => 'sale',
                        'relatedId' => $request->input('saleInvoiceNo')
                    ]);
                }
            }
            $transaction->amount = $amount;

            // Update SaleInvoice paidAmount and dueAmount after payment
            $totalPaid = Transaction::where('type', 'sale')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where('creditId', 4)
                ->sum('amount');

            $totalAmountCalc = Transaction::where('type', 'sale')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where('debitId', 4)
                ->sum('amount');

            $totalReturnAmount = Transaction::where('type', 'sale_return')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where('creditId', 4)
                ->sum('amount');

            $instantReturnAmount = Transaction::where('type', 'sale_return')
                ->where('relatedId', $request->input('saleInvoiceNo'))
                ->where('debitId', 4)
                ->sum('amount');

            $calculatedDueAmount = (($totalAmountCalc - $totalReturnAmount) - $totalPaid) + $instantReturnAmount;

            // Update customer's current_due_amount
            if ($customer) {
                $newCustomerDue = max(0, $customer->current_due_amount - $amount);
                $customer->update(['current_due_amount' => takeUptoThreeDecimal($newCustomerDue)]);

                // Update sale invoice - only update paidAmount and dueAmount
                SaleInvoice::where('id', $request->input('saleInvoiceNo'))
                    ->update([
                        'paidAmount' => takeUptoThreeDecimal($totalPaid),
                        'dueAmount' => takeUptoThreeDecimal($calculatedDueAmount),
                        'customer_current_due' => takeUptoThreeDecimal($newCustomerDue)
                    ]);
                
                // Update all future invoices for this customer
                $futureInvoices = SaleInvoice::where('customerId', $saleInvoice->customerId)
                    ->where('id', '>', $request->input('saleInvoiceNo'))
                    ->orderBy('id', 'asc')
                    ->get();
                
                foreach ($futureInvoices as $futureInvoice) {
                    // Calculate previous due for this invoice (due before this invoice was created)
                    $previousInvoicesDue = SaleInvoice::where('customerId', $saleInvoice->customerId)
                        ->where('id', '<', $futureInvoice->id)
                        ->get()
                        ->sum(function($inv) {
                            return $inv->dueAmount ?? 0;
                        });
                    
                    // Recalculate total_calculation
                    $invoiceAmount = $futureInvoice->totalAmount ?? 0;
                    $commission = $futureInvoice->commission_value ?? 0;
                    $bagAmt = ($futureInvoice->bag_quantity ?? 0) * ($futureInvoice->bag_price ?? 0);
                    $newTotalCalculation = $invoiceAmount + $commission + $bagAmt + $previousInvoicesDue;
                    
                    $futureInvoice->update([
                        'customer_previous_due' => takeUptoThreeDecimal($previousInvoicesDue),
                        'total_calculation' => takeUptoThreeDecimal($newTotalCalculation),
                        'customer_current_due' => takeUptoThreeDecimal($newCustomerDue)
                    ]);
                }
            } else {
                // Update the sale invoice
                SaleInvoice::where('id', $request->input('saleInvoiceNo'))
                    ->update([
                        'paidAmount' => takeUptoThreeDecimal($totalPaid),
                        'dueAmount' => takeUptoThreeDecimal($calculatedDueAmount)
                    ]);
            }

            $converted = $transaction ? arrayKeysToCamelCase($transaction->toArray()) : [];
            $finalResult = [
                'transaction' => $converted,
            ];

            DB::commit();
            return response()->json($finalResult, 201);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred during create paymentSale Please try again later.'], 500);
        }
    }

    // get all the paymentSaleInvoice controller method
    public function getAllPaymentSaleInvoice(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $allPaymentSaleInvoice = Transaction::where('type', 'sale')
                    ->orderBy('id', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($allPaymentSaleInvoice->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting paymentSale Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'info') {
            try {
                $aggregations = Transaction::where('type', 'sale')
                    ->selectRaw('COUNT(id) as countedId, SUM(amount) as amount')
                    ->first();

                $finalResult = [
                    '_count' => [
                        'id' => $aggregations->countedId,
                    ],
                    '_sum' => [
                        'amount' => $aggregations->amount,
                    ],
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting paymentSale Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $getAllPaymentSaleInvoice = Transaction::when($request->query('date'), function ($query) use ($request) {
                    $dates = explode(',', $request->query('date'));
                    return $query->whereIn(DB::raw('DATE(date)'), $dates);
                })
                    ->when($request->query('type'), function ($query) use ($request) {
                        return $query->whereIn('type', explode(',', $request->query('type')));
                    })
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $aggregations = Transaction::where('type', 'sale')
                    ->selectRaw('COUNT(id) as count, SUM(amount) as amount')
                    ->first();


                $allPaymentSaleInvoiceCount = Transaction::when($request->query('date'), function ($query) use ($request) {
                    $dates = explode(',', $request->query('date'));
                    return $query->whereIn(DB::raw('DATE(date)'), $dates);
                })
                    ->when($request->query('type'), function ($query) use ($request) {
                        return $query->whereIn('type', explode(',', $request->query('type')));
                    })
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->count();

                $converted = arrayKeysToCamelCase($getAllPaymentSaleInvoice->toArray());
                $finalResult = [
                    'getAllPaymentSaleInvoice' => $converted,
                    'totalPaymentSaleInvoiceCount' => $aggregations->count,
                    'totalAmount' => $aggregations->amount,
                    'totalPaymentSaleInvoice' => $allPaymentSaleInvoiceCount,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting paymentSale Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid query!'], 400);
        }
    }
  
}
