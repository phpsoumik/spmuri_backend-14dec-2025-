<?php

namespace App\Http\Controllers;

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
            $date = Carbon::parse($request->input('date'))->toDateString();
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

            // validation with due
            if ($totalDueAmount < $amount) {
                return response()->json(['error' => 'Amount cannot be greater than due!'], 400);
            }

            // new transactions will be created as journal entry for paid amount
            foreach ($request->paidAmount as $amountData) {
                if ($amountData['amount'] > 0) {
                    $transaction = Transaction::create([
                        'date' => new DateTime($date),
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
            
            // Update the sale invoice with new amounts
            SaleInvoice::where('id', $request->input('saleInvoiceNo'))
                ->update([
                    'paidAmount' => takeUptoThreeDecimal($totalPaid),
                    'dueAmount' => takeUptoThreeDecimal($calculatedDueAmount)
                ]);

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

    // Simple get method - no complex queries
    public function getAllPaymentSaleInvoice(Request $request): JsonResponse
    {
        try {
            $allPaymentSaleInvoice = Transaction::where('type', 'sale')
                ->orderBy('id', 'desc')
                ->get();

            $converted = arrayKeysToCamelCase($allPaymentSaleInvoice->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting paymentSale Please try again later.'], 500);
        }
    }
}