<?php

namespace App\Http\Controllers;

use App\Models\{Product, Transaction, PurchaseInvoiceProduct, PurchaseInvoice, ReturnPurchaseInvoice};
use App\Models\Supplier;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    //create purchaseInvoice controller method
    public function createSinglePurchaseInvoice(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validate = Validator($request->all(), [
                'date' => 'required|date',
                'purchaseInvoiceProduct' => 'required|array|min:1',
                'purchaseInvoiceProduct.*.productId' => 'required|integer',
                'purchaseInvoiceProduct.*.productQuantity' => 'required|integer',
                'purchaseInvoiceProduct.*.productUnitPurchasePrice' => 'required|numeric',
                'purchaseInvoiceProduct.*.tax' => 'required|numeric',
                'supplierId' => 'required|integer|exists:supplier,id',
                'note' => 'nullable|string',
            ]);
            if ($validate->fails()) {
                return response()->json(['error' => $validate->errors()->first()], 400);
            }

            $totalTax = 0;
            $totalPurchasePrice = 0;
            foreach ($request->purchaseInvoiceProduct as $item) {
                $productUnitPurchasePrice = (float) $item['productUnitPurchasePrice'] * (float) $item['productQuantity'];
                $taxAmount = ($productUnitPurchasePrice * (float) $item['tax']) / 100;

                $totalTax = $totalTax + $taxAmount;
                $totalPurchasePrice += $productUnitPurchasePrice;
            }

            // Calculate total commission amount
            $totalCommissionAmount = 0;
            $commissions = $request->input('commissions', []);
            foreach ($commissions as $commission) {
                $totalCommissionAmount += (float) ($commission['charge'] ?? 0);
            }

            $totalPaidAmount = 0;
            foreach ($request->paidAmount as $amountData) {
                $totalPaidAmount += $amountData['amount'];
            }

            if ($totalPaidAmount > $totalTax + $totalPurchasePrice + $totalCommissionAmount) {
                return response()->json(['error' => 'Paid Amount cannot be bigger than total amount including commission!'], 400);
            }

            $date = Carbon::parse($request->input('date'));
            $createdInvoice = PurchaseInvoice::create([
                'date' => $date,
                'invoiceMemoNo' => $request->input('invoiceMemoNo') ? $request->input('invoiceMemoNo') : null,
                'totalAmount' => takeUptoThreeDecimal((float) $totalPurchasePrice),
                'totalTax' => takeUptoThreeDecimal((float) $totalTax),
                'paidAmount' => $totalPaidAmount ? takeUptoThreeDecimal((float) $totalPaidAmount) : 0,
                'dueAmount' => takeUptoThreeDecimal((float) $totalPurchasePrice + (float) $totalTax + (float) $totalCommissionAmount - (float) $totalPaidAmount),
                'supplierId' => $request->input('supplierId'),
                'note' => $request->input('note'),
                'supplierMemoNo' => $request->input('supplierMemoNo'),
                'lorryNo' => $request->input('lorryNo'),
                'commissions' => $commissions,
                'total_commission_amount' => takeUptoThreeDecimal((float) $totalCommissionAmount),
            ]);

            if ($createdInvoice) {
                foreach ($request->purchaseInvoiceProduct as $item) {
                    $productFinalAmount = (int) $item['productQuantity'] * (float) $item['productUnitPurchasePrice'];

                    $taxAmount = ($productFinalAmount * $item['tax']) / 100;

                    PurchaseInvoiceProduct::create([
                        'invoiceId' => $createdInvoice->id,
                        'productId' => $item['productId'],
                        'productQuantity' => $item['productQuantity'],
                        'bag' => $item['bag'] ?? 0,
                        'kg' => $item['kg'] ?? 0,
                        'grossWeight' => $item['grossWeight'] ?? 0,
                        'tareWeight' => $item['tareWeight'] ?? 0,
                        'netWeight' => $item['netWeight'] ?? 0,
                        'productUnitPurchasePrice' => takeUptoThreeDecimal((float) $item['productUnitPurchasePrice']),
                        'productFinalAmount' => takeUptoThreeDecimal((float) $productFinalAmount),
                        'tax' => $item['tax'],
                        'taxAmount' => takeUptoThreeDecimal((float) $taxAmount),
                    ]);
                }
            }

            // transaction for purchase account payable for total amount without tax
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 3,
                'creditId' => 5,
                'amount' => takeUptoThreeDecimal($totalPurchasePrice),
                'particulars' => "Total purchase price without tax on Purchase Invoice #$createdInvoice->id",
                'type' => 'purchase',
                'relatedId' => $createdInvoice->id,
            ]);

            // transaction for purchase account payable for tax amount
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 15,
                'creditId' => 5,
                'amount' => takeUptoThreeDecimal($totalTax),
                'particulars' => "Total purchase tax on Purchase Invoice #$createdInvoice->id",
                'type' => 'purchase',
                'relatedId' => $createdInvoice->id,
            ]);

            // transaction for commission amount
            if ($totalCommissionAmount > 0) {
                Transaction::create([
                    'date' => new DateTime($date),
                    'debitId' => 9, // Use existing expense account (Cost of Sales)
                    'creditId' => 5,
                    'amount' => takeUptoThreeDecimal($totalCommissionAmount),
                    'particulars' => "Commission charges on Purchase Invoice #$createdInvoice->id",
                    'type' => 'purchase',
                    'relatedId' => $createdInvoice->id,
                ]);
            }

            // pay on purchase transaction create
            foreach ($request->paidAmount as $amountData) {
                if ($amountData['amount'] > 0) {
                    Transaction::create([
                        'date' => new DateTime($date),
                        'debitId' => 5,
                        'creditId' => $amountData['paymentType'] ? $amountData['paymentType'] : 1,
                        'amount' => takeUptoThreeDecimal((float) $amountData['amount']),
                        'particulars' => "Paid on Purchase Invoice #$createdInvoice->id",
                        'type' => 'purchase',
                        'relatedId' => $createdInvoice->id,
                    ]);
                }
            }

            // iterate through all products of this purchase invoice and add product quantity, update product purchase price to database
            foreach ($request->purchaseInvoiceProduct as $item) {
                $productId = (int) $item['productId'];
                $productQuantity = (int) $item['productQuantity'];
                $productSalePrice = 0; // No sale price for purchase products

                // Get purchase product data
                $requestSinglePrice = (float) $item['productUnitPurchasePrice'];

                // Update purchase product quantity and price
                \App\Models\PurchaseProduct::where('id', $productId)->update([
                    'purchase_price' => takeUptoThreeDecimal($requestSinglePrice),
                ]);
            }

            // Calculate and store supplier due amounts
            $supplier = Supplier::find($request->input('supplierId'));
            if ($supplier) {
                $supplierPreviousDue = $supplier->current_due_amount ?? 0;
                $invoiceDue = $createdInvoice->dueAmount;
                $supplierCurrentDue = $supplierPreviousDue + $invoiceDue;
                
                // Update purchase invoice with supplier due info
                $createdInvoice->update([
                    'supplier_previous_due' => $supplierPreviousDue,
                    'supplier_current_due' => $supplierCurrentDue,
                ]);
                
                // Update supplier's current due amount
                $supplier->update(['current_due_amount' => $supplierCurrentDue]);
            }
            
            $converted = arrayKeysToCamelCase($createdInvoice->toArray());
            DB::commit();
            return response()->json(['createdInvoice' => $converted], 201);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // get all the purchaseInvoice controller method
    public function getAllPurchaseInvoice(Request $request): JsonResponse
    {
        if ($request->query('query') === 'info') {
            try {
                $aggregation = PurchaseInvoice::selectRaw('COUNT(id) as id')->first();

                // transaction of the total amount
                $totalAmount = Transaction::where('type', 'purchase')
                    ->where(function ($query) {
                        $query->where('creditId', 5);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'purchase')
                    ->where(function ($query) {
                        $query->orWhere('debitId', 5);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'purchase_return')
                    ->where(function ($query) {
                        $query->where('debitId', 5);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'purchase_return')
                    ->where(function ($query) {
                        $query->where('creditId', 5);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // calculation of due amount
                $totalDueAmount = $totalAmount->amount - $totalAmountOfReturn->amount - $totalPaidAmount->amount + $totalInstantReturnAmount->amount;

                $result = [
                    '_count' => [
                        'id' => $aggregation->id,
                    ],
                    '_sum' => [
                        'totalAmount' => takeUptoThreeDecimal($totalAmount->amount),
                        'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                        'paidAmount' => takeUptoThreeDecimal($totalPaidAmount->amount),
                        'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->amount),
                        'instantReturnPaidAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->amount),
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } elseif ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());

                $allPurchase = PurchaseInvoice::where('id', $request->query('key'))
                    ->orWhere('supplierMemoNo', 'LIKE', '%' . $request->query('key') . '%')
                    ->with('purchaseInvoiceProduct')
                    ->orderBy('created_at', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $total = PurchaseInvoice::where('id', $request->query('key'))
                    ->orWhere('supplierMemoNo', 'LIKE', '%' . $request->query('key') . '%')
                    ->count();

                $converted = arrayKeysToCamelCase($allPurchase->toArray());
                $finalResult = [
                    'getAllPurchaseInvoice' => $converted,
                    'totalPurchaseInvoice' => $total,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } elseif ($request->query('query') === 'report') {
            try {
                $purchaseInvoices = PurchaseInvoice::with('purchaseInvoiceProduct', 'purchaseInvoiceProduct.product:id,name', 'supplier:id,name')
                    ->orderBy('created_at', 'desc')
                    ->when($request->query('supplierId'), function ($query) use ($request) {
                        return $query->where('supplierId', $request->query('supplierId'));
                    })
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->get();

                $purchaseInvoiceIds = $purchaseInvoices->pluck('id')->toArray();

                // transaction of the total amount
                $totalAmount = Transaction::where('type', 'purchase')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->where('creditId', 5);
                    })
                    ->get();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'purchase')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->orWhere('debitId', 5);
                    })
                    ->get();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'purchase_return')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->where('debitId', 5);
                    })
                    ->get();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'purchase_return')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->where('creditId', 5);
                    })
                    ->get();

                // calculate grand total due amount
                $totalDueAmount = $totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount') - $totalPaidAmount->sum('amount') + $totalInstantReturnAmount->sum('amount');

                $allPurchaseInvoice = $purchaseInvoices->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {
                    $totalAmount = $totalAmount
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase' && $trans->creditId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $totalPaid = $totalPaidAmount
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase' && $trans->debitId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $totalReturnAmount = $totalAmountOfReturn
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase_return' && $trans->debitId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $instantPaidReturnAmount = $totalInstantReturnAmount
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase_return' && $trans->creditId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $totalDueAmount = $totalAmount - $totalReturnAmount - $totalPaid + $instantPaidReturnAmount;

                    $item->paidAmount = $totalPaid;
                    $item->instantPaidReturnAmount = $instantPaidReturnAmount;
                    $item->dueAmount = $totalDueAmount;
                    $item->returnAmount = $totalReturnAmount;
                    return $item;
                });

                // calculate total paidAmount and dueAmount from allPurchaseInvoice and attach it to aggregations
                $totalAmount = $totalAmount->sum('amount');
                $totalPaidAmount = $totalPaidAmount->sum('amount');
                $totalDueAmount = $totalDueAmount;
                $totalReturnAmount = $totalAmountOfReturn->sum('amount');
                $instantPaidReturnAmount = $totalInstantReturnAmount->sum('amount');
                $counted = $purchaseInvoices->count('id');

                $modifiedData = collect($allPurchaseInvoice);

                $aggregations = [
                    '_count' => [
                        'id' => $counted,
                    ],
                    '_sum' => [
                        'totalAmount' => $totalAmount,
                        'paidAmount' => $totalPaidAmount,
                        'dueAmount' => $totalDueAmount,
                        'totalReturnAmount' => $totalReturnAmount,
                        'instantPaidReturnAmount' => $instantPaidReturnAmount,
                    ],
                ];

                $converted = arrayKeysToCamelCase($modifiedData->toArray());
                $finalResult = [
                    'aggregations' => $aggregations,
                    'getAllPurchaseInvoice' => $converted,
                    'totalPurchaseInvoice' => $counted,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } elseif ($request->query()) {
            try {
                $pagination = getPagination($request->query());

                $purchaseInvoices = PurchaseInvoice::with(['purchaseInvoiceProduct', 'purchaseInvoiceProduct.product:id,name', 'supplier:id,name,current_due_amount'])
                    ->orderBy('created_at', 'desc')
                    ->when($request->query('supplierId'), function ($query) use ($request) {
                        return $query->whereIn('supplierId', explode(',', $request->query('supplierId')));
                    })
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                // Load payment transactions for each invoice
                foreach ($purchaseInvoices as $invoice) {
                    $paymentTransactions = Transaction::where('type', 'purchase')
                        ->where('relatedId', $invoice->id)
                        ->where('debitId', 5)
                        ->with('credit:id,name')
                        ->get();
                    $invoice->paymentMethods = $paymentTransactions->pluck('credit.name')->unique()->values();
                }

                $purchaseInvoiceIds = $purchaseInvoices->pluck('id')->toArray();

                // transaction of the total amount
                $totalAmount = Transaction::where('type', 'purchase')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->where('creditId', 5);
                    })
                    ->get();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'purchase')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->orWhere('debitId', 5);
                    })
                    ->get();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'purchase_return')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->where('debitId', 5);
                    })
                    ->get();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'purchase_return')
                    ->whereIn('relatedId', $purchaseInvoiceIds)
                    ->where(function ($query) {
                        $query->where('creditId', 5);
                    })
                    ->get();

                // calculate grand total due amount
                $totalDueAmount = $totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount') - $totalPaidAmount->sum('amount') + $totalInstantReturnAmount->sum('amount');

                $allPurchaseInvoice = $purchaseInvoices->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {
                    $totalAmount = $totalAmount
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase' && $trans->creditId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $totalPaid = $totalPaidAmount
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase' && $trans->debitId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $totalReturnAmount = $totalAmountOfReturn
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase_return' && $trans->debitId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $instantPaidReturnAmount = $totalInstantReturnAmount
                        ->filter(function ($trans) use ($item) {
                            return $trans->relatedId === $item->id && $trans->type === 'purchase_return' && $trans->creditId === 5;
                        })
                        ->reduce(function ($acc, $current) {
                            return $acc + $current->amount;
                        }, 0);

                    $totalDueAmount = $totalAmount - $totalReturnAmount - $totalPaid + $instantPaidReturnAmount;

                    // Update with calculated values for accuracy
                    $item->paidAmount = takeUptoThreeDecimal($totalPaid);
                    $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                    $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                    
                    // Set supplier_current_due from supplier data
                    $supplierCurrentDue = $item->supplier->current_due_amount ?? 0;
                    $item->supplier_current_due = $supplierCurrentDue;
                    $item->supplierCurrentDue = $supplierCurrentDue; // Also add camelCase version
                    
                    return $item;
                });

                // calculate total paidAmount and dueAmount from allPurchaseInvoice and attach it to aggregations
                $totalAmount = $totalAmount->sum('amount');
                $totalPaidAmount = $totalPaidAmount->sum('amount');
                $totalReturnAmount = $totalAmountOfReturn->sum('amount');
                $instantPaidReturnAmount = $totalInstantReturnAmount->sum('amount');
                $counted = $allPurchaseInvoice->count('id');

                $modifiedData = collect($allPurchaseInvoice)
                    ->skip($pagination['skip'])
                    ->take($pagination['limit']);

                $aggregations = [
                    '_count' => [
                        'id' => $counted,
                    ],
                    '_sum' => [
                        'totalAmount' => takeUptoThreeDecimal($totalAmount),
                        'paidAmount' => takeUptoThreeDecimal($totalPaidAmount),
                        'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                        'totalReturnAmount' => takeUptoThreeDecimal($totalReturnAmount),
                        'instantPaidReturnAmount' => takeUptoThreeDecimal($instantPaidReturnAmount),
                    ],
                ];

                $converted = arrayKeysToCamelCase($modifiedData->toArray());
                $finalResult = [
                    'aggregations' => $aggregations,
                    'getAllPurchaseInvoice' => $converted,
                    'totalPurchaseInvoice' => $counted,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'invalid query!'], 400);
        }
    }

    // get a single purchaseInvoice controller method
    public function getSinglePurchaseInvoice(Request $request, $id): JsonResponse
    {
        try {
            // get single purchase invoice information with products
            $singlePurchaseInvoice = PurchaseInvoice::where('id', $id)->with('purchaseInvoiceProduct.product', 'supplier')->first();

            if (!$singlePurchaseInvoice) {
                return response()->json(['error' => 'This invoice not Found'], 400);
            }

            // transaction of the total amount
            $totalAmount = Transaction::where('type', 'purchase')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('creditId', 5);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            // transaction of the paidAmount
            $totalPaidAmount = Transaction::where('type', 'purchase')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->orWhere('debitId', 5);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            // transaction of the total amount
            $totalAmountOfReturn = Transaction::where('type', 'purchase_return')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('debitId', 5);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            // transaction of the total instant return
            $totalInstantReturnAmount = Transaction::where('type', 'purchase_return')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('creditId', 5);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            // calculate grand total due amount
            $totalDueAmount = $totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount') - $totalPaidAmount->sum('amount') + $totalInstantReturnAmount->sum('amount');

            // get return purchaseInvoice information with products of this purchase invoice
            $returnPurchaseInvoice = ReturnPurchaseInvoice::where('purchaseInvoiceId', $id)->with('returnPurchaseInvoiceProduct', 'returnPurchaseInvoiceProduct.product')->orderBy('id', 'desc')->get();

            $status = 'UNPAID';
            if ($totalDueAmount <= 0.0) {
                $status = 'PAID';
            }

            // get all transactions related to this purchase invoice
            $transactions = Transaction::where('relatedId', $id)
                ->where(function ($query) {
                    $query->orWhere('type', 'purchase')->orWhere('type', 'purchase_return');
                })
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            $convertedSingleInvoice = arrayKeysToCamelCase($singlePurchaseInvoice->toArray());
            $convertedReturnInvoice = arrayKeysToCamelCase($returnPurchaseInvoice->toArray());
            $convertedTransactions = arrayKeysToCamelCase($transactions->toArray());
            $finalResult = [
                'status' => $status,
                'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                'totalPaidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                'singlePurchaseInvoice' => $convertedSingleInvoice,
                'returnPurchaseInvoice' => $convertedReturnInvoice,
                'transactions' => $convertedTransactions,
            ];

            return response()->json($finalResult, 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // update purchaseInvoice controller method
    public function updatePurchaseInvoice(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validate = Validator($request->all(), [
                'date' => 'required|date',
                'products' => 'required|array|min:1',
                'products.*.productId' => 'required|integer',
                'products.*.productQuantity' => 'required|integer',
                'products.*.productPurchasePrice' => 'required|numeric',
                'supplierId' => 'required|integer|exists:supplier,id',
                'note' => 'nullable|string',
            ]);
            if ($validate->fails()) {
                return response()->json(['error' => $validate->errors()->first()], 400);
            }

            $purchaseInvoice = PurchaseInvoice::find($id);
            if (!$purchaseInvoice) {
                return response()->json(['error' => 'Purchase invoice not found'], 404);
            }

            // Delete existing products and transactions
            PurchaseInvoiceProduct::where('invoiceId', $id)->delete();
            Transaction::where('relatedId', $id)->where('type', 'purchase')->delete();

            $totalTax = 0;
            $totalPurchasePrice = 0;
            foreach ($request->products as $item) {
                $productUnitPurchasePrice = (float) $item['productPurchasePrice'] * (float) $item['productQuantity'];
                $taxAmount = ($productUnitPurchasePrice * (float) ($item['tax'] ?? 0)) / 100;
                $totalTax += $taxAmount;
                $totalPurchasePrice += $productUnitPurchasePrice;
            }

            // Calculate total commission amount
            $totalCommissionAmount = 0;
            $commissions = $request->input('commissions', []);
            foreach ($commissions as $commission) {
                $totalCommissionAmount += (float) ($commission['charge'] ?? 0);
            }

            $date = Carbon::parse($request->input('date'));
            $purchaseInvoice->update([
                'date' => $date,
                'totalAmount' => takeUptoThreeDecimal((float) $totalPurchasePrice),
                'totalTax' => takeUptoThreeDecimal((float) $totalTax),
                'dueAmount' => takeUptoThreeDecimal((float) $totalPurchasePrice + (float) $totalTax + (float) $totalCommissionAmount),
                'supplierId' => $request->input('supplierId'),
                'note' => $request->input('note'),
                'lorryNo' => $request->input('lorryNo'),
                'commissions' => $commissions,
                'total_commission_amount' => takeUptoThreeDecimal((float) $totalCommissionAmount),
            ]);

            // Add updated products
            foreach ($request->products as $item) {
                $productFinalAmount = (int) $item['productQuantity'] * (float) $item['productPurchasePrice'];
                $taxAmount = ($productFinalAmount * ($item['tax'] ?? 0)) / 100;

                PurchaseInvoiceProduct::create([
                    'invoiceId' => $purchaseInvoice->id,
                    'productId' => $item['productId'],
                    'productQuantity' => $item['productQuantity'],
                    'bag' => $item['bag'] ?? 0,
                    'kg' => $item['kg'] ?? 0,
                    'grossWeight' => $item['grossWeight'] ?? 0,
                    'tareWeight' => $item['tareWeight'] ?? 0,
                    'netWeight' => $item['netWeight'] ?? 0,
                    'productUnitPurchasePrice' => takeUptoThreeDecimal((float) $item['productPurchasePrice']),
                    'productFinalAmount' => takeUptoThreeDecimal((float) $productFinalAmount),
                    'tax' => $item['tax'] ?? 0,
                    'taxAmount' => takeUptoThreeDecimal((float) $taxAmount),
                ]);
            }

            // Create new transactions
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 3,
                'creditId' => 5,
                'amount' => takeUptoThreeDecimal($totalPurchasePrice),
                'particulars' => "Updated purchase on Purchase Invoice #$purchaseInvoice->id",
                'type' => 'purchase',
                'relatedId' => $purchaseInvoice->id,
            ]);

            // transaction for tax amount
            if ($totalTax > 0) {
                Transaction::create([
                    'date' => new DateTime($date),
                    'debitId' => 15,
                    'creditId' => 5,
                    'amount' => takeUptoThreeDecimal($totalTax),
                    'particulars' => "Updated tax on Purchase Invoice #$purchaseInvoice->id",
                    'type' => 'purchase',
                    'relatedId' => $purchaseInvoice->id,
                ]);
            }

            // transaction for commission amount
            if ($totalCommissionAmount > 0) {
                Transaction::create([
                    'date' => new DateTime($date),
                    'debitId' => 9,
                    'creditId' => 5,
                    'amount' => takeUptoThreeDecimal($totalCommissionAmount),
                    'particulars' => "Updated commission on Purchase Invoice #$purchaseInvoice->id",
                    'type' => 'purchase',
                    'relatedId' => $purchaseInvoice->id,
                ]);
            }

            $converted = arrayKeysToCamelCase($purchaseInvoice->fresh()->toArray());
            DB::commit();
            return response()->json(['updatedInvoice' => $converted], 200);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // delete purchaseInvoice controller method
    public function deletePurchaseInvoice($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Check if purchase invoice exists
            $purchaseInvoice = PurchaseInvoice::find($id);
            if (!$purchaseInvoice) {
                return response()->json(['error' => 'Purchase invoice not found'], 404);
            }

            // Delete all related transactions first
            Transaction::where('relatedId', $id)
                ->where('type', 'purchase')
                ->delete();

            // Delete related purchase invoice products
            PurchaseInvoiceProduct::where('invoiceId', $id)->delete();

            // Delete the purchase invoice
            $purchaseInvoice->delete();

            DB::commit();
            return response()->json([
                'message' => 'success',
                'action' => 'deleted'
            ], 200);

        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // Get all purchase invoice products for raw materials
    public function getPurchaseInvoiceProducts(Request $request): JsonResponse
    {
        try {
            $products = \App\Models\PurchaseProduct::select('id', 'name', 'purchase_price', 'sku')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'message' => 'success',
                'data' => $products
            ], 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }
}
