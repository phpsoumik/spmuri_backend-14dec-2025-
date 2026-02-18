<?php

namespace App\Http\Controllers;

use DateTime;
use Exception;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ReturnSaleInvoice;
use Illuminate\Http\JsonResponse;
use App\Models\SaleInvoiceProduct;
use App\Models\ReadyProductStockItem;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;

class SaleInvoiceController extends Controller
{
    // create a single SaleInvoice controller method
    public function createSingleSaleInvoice(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $validate = validator($request->all(), [
                'date' => 'required|date',
                'saleInvoiceProduct' => 'required|array|min:1',
                'saleInvoiceProduct.*.productId' => 'required|distinct',
                'saleInvoiceProduct.*.productQuantity' => 'required|integer|min:1',
                'saleInvoiceProduct.*.bag' => 'nullable|numeric|min:0',
                'saleInvoiceProduct.*.kg' => 'nullable|numeric|min:0',
                'saleInvoiceProduct.*.productUnitSalePrice' => 'required|numeric|min:0',
                'customerId' => 'required|integer|exists:customer,id',
                'userId' => 'required|integer|exists:users,id',
            ]);

            if ($validate->fails()) {
                return response()->json(['error' => $validate->errors()->first()], 400);
            }

            // Get all the products (ready product stock items)
            $allProducts = collect($request->input('saleInvoiceProduct'))->map(function ($item) {
                // Check if it's a ready product stock item (format: ready_123)
                if (strpos($item['productId'], 'ready_') === 0) {
                    $stockItemId = str_replace('ready_', '', $item['productId']);
                    $stockItem = ReadyProductStockItem::find($stockItemId);
                    if ($stockItem) {
                        return (object) [
                            'id' => $item['productId'],
                            'productPurchasePrice' => $stockItem->unit_price,
                            'name' => $stockItem->rawMaterial->name ?? 'Ready Product'
                        ];
                    }
                }
                
                // Check if it's a numeric ID that might be a ready product stock item
                $productId = (int)$item['productId'];
                $regularProduct = Product::find($productId);
                
                // If regular product doesn't exist, check if it's a ready product stock item
                if (!$regularProduct) {
                    $stockItem = ReadyProductStockItem::find($productId);
                    if ($stockItem) {
                        return (object) [
                            'id' => "ready_{$productId}", // Convert to ready format
                            'productPurchasePrice' => $stockItem->unit_price,
                            'name' => $stockItem->rawMaterial->name ?? 'Ready Product'
                        ];
                    }
                }
                
                return $regularProduct;
            })->filter();

            $totalDiscount = 0; // its discount amount
            $totalTax = 0; //its only total vat amount
            $totalSalePriceWithDiscount = 0;  //its total amount included discount but excluded vat

            foreach ($request->saleInvoiceProduct as $item) {
                $productFinalAmount = ((int)$item['productQuantity'] * (float)$item['productUnitSalePrice']) - (float)$item['productDiscount'];

                $totalDiscount = $totalDiscount + (float)$item['productDiscount'];
                $taxAmount = ($productFinalAmount * (float)$item['tax']) / 100;

                $totalTax = $totalTax + $taxAmount;
                $totalSalePriceWithDiscount += $productFinalAmount;
            }
            // Validate stock before processing
            $requestedProducts = collect($request->input('saleInvoiceProduct'));
            
            foreach ($request->saleInvoiceProduct as $item) {
                $isReadyProduct = false;
                $stockItemId = null;
                
                if (strpos($item['productId'], 'ready_') === 0) {
                    $isReadyProduct = true;
                    $stockItemId = str_replace('ready_', '', $item['productId']);
                } else {
                    $productId = (int)$item['productId'];
                    $regularProduct = Product::find($productId);
                    if (!$regularProduct && ReadyProductStockItem::find($productId)) {
                        $isReadyProduct = true;
                        $stockItemId = $productId;
                    }
                }
                
                if ($isReadyProduct && $stockItemId) {
                    $stockItem = ReadyProductStockItem::find($stockItemId);
                    if (!$stockItem) {
                        return response()->json(['error' => "Stock item not found for ID: {$stockItemId}"], 400);
                    }
                    
                    $productName = $stockItem->saleProduct->name ?? $stockItem->ready_product_name ?? 'Ready Product';
                    $requestedQuantity = (float)$item['productQuantity'];
                    
                    if ($stockItem->current_stock_kg <= 0) {
                        return response()->json(['error' => "Product '{$productName}' is out of stock!"], 400);
                    }
                    
                    if ($requestedQuantity > $stockItem->current_stock_kg) {
                        return response()->json([
                            'error' => "Insufficient stock for '{$productName}'! Available: {$stockItem->current_stock_kg} kg, Required: {$requestedQuantity} kg"
                        ], 400);
                    }
                }
            }

            // calculate total purchase price
            $totalPurchasePrice = 0;
            foreach ($request->saleInvoiceProduct as $item) {
                $product = $allProducts->firstWhere('id', $item['productId']);
                if ($product) {
                    $totalPurchasePrice += (float)$product->productPurchasePrice * (float)$item['productQuantity'];
                }
            }

            $totalPaidAmount = 0;
            $paidAmountArray = is_array($request->paidAmount) ? $request->paidAmount : [];
            foreach ($paidAmountArray as $amountData) {
                $totalPaidAmount += isset($amountData['amount']) ? $amountData['amount'] : 0;
            }

            // GST Calculation for validation
            $gstApplicable = $request->input('gstEnabled', false); // Changed from gst_applicable to gstEnabled
            $cgstRate = $request->input('cgst_rate', 2.5);
            $sgstRate = $request->input('sgst_rate', 2.5);
            
            $subtotal = $totalSalePriceWithDiscount;
            $cgstAmount = 0;
            $sgstAmount = 0;
            $totalGst = 0;
            $grandTotal = $subtotal;
            
            if ($gstApplicable) {
                $cgstAmount = ($subtotal * $cgstRate) / 100;
                $sgstAmount = ($subtotal * $sgstRate) / 100;
                $totalGst = $cgstAmount + $sgstAmount;
                $grandTotal = $subtotal + $totalGst;
            }
            
            if($totalPaidAmount > $grandTotal + $totalTax){
                return response()->json(['error' => 'Paid amount cannot be greater than total amount!'], 400);
            }


            // Due amount
            $due = $totalSalePriceWithDiscount + $totalTax - (float)$totalPaidAmount;


            // Convert all incoming date to a specific format
            $date = Carbon::parse($request->input('date'));
            $dueDate = $request->input('dueDate') ? Carbon::parse($request->input('dueDate')) : null;

            // GST Calculation
            $gstApplicable = $request->input('gstEnabled', false); // Changed from gst_applicable to gstEnabled
            $cgstRate = $request->input('cgst_rate', 2.5);
            $sgstRate = $request->input('sgst_rate', 2.5);
            
            $subtotal = $totalSalePriceWithDiscount;
            $cgstAmount = 0;
            $sgstAmount = 0;
            $totalGst = 0;
            $grandTotal = $subtotal;
            
            if ($gstApplicable) {
                $cgstAmount = ($subtotal * $cgstRate) / 100;
                $sgstAmount = ($subtotal * $sgstRate) / 100;
                $totalGst = $cgstAmount + $sgstAmount;
                $grandTotal = $subtotal + $totalGst;
            }
            
            // Update due calculation with GST
            $due = $grandTotal - (float)$totalPaidAmount;

            // Calculate total calculation with commission and bag
            $commissionValue = (float)$request->input('commissionValue', 0);
            $bagQuantity = (float)$request->input('bagQuantity', 0);
            $bagPrice = (float)$request->input('bagPrice', 0);
            $bagAmount = $bagQuantity * $bagPrice;
            
            // Get customer previous due
            $customer = \App\Models\Customer::find($request->input('customerId'));
            $customerPreviousDue = $customer ? ($customer->current_due_amount ?? 0) : 0;
            
            // Calculate total_calculation = product amount + commission + bag amount + previous due
            $totalCalculation = $totalSalePriceWithDiscount + $commissionValue + $bagAmount + $customerPreviousDue;

            // Create sale invoice
            $createdInvoice = SaleInvoice::create([
                'date' => $date,
                'invoiceMemoNo' => $request->input('invoiceMemoNo') ? $request->input('invoiceMemoNo') : null,
                'totalAmount' => takeUptoThreeDecimal($totalSalePriceWithDiscount),
                'totalTaxAmount' => $totalTax ? takeUptoThreeDecimal($totalTax) : 0,
                'totalDiscountAmount' => $totalDiscount ? takeUptoThreeDecimal($totalDiscount) : 0,
                'paidAmount' => $totalPaidAmount ? takeUptoThreeDecimal((float)$totalPaidAmount) : 0,
                'profit' => takeUptoThreeDecimal($totalSalePriceWithDiscount - $totalPurchasePrice),
                'dueAmount' => $due ? takeUptoThreeDecimal($due) : 0,
                'note' => $request->input('note') ?? null,
                'address' => $request->input('address'),
                'commission_type' => $request->input('commissionType'),
                'commission_value' => $request->input('commissionValue') ? takeUptoThreeDecimal((float)$request->input('commissionValue')) : 0,
                'bag_quantity' => $request->input('bagQuantity') ? takeUptoThreeDecimal((float)$request->input('bagQuantity')) : 0,
                'bag_price' => $request->input('bagPrice') ? takeUptoThreeDecimal((float)$request->input('bagPrice')) : 0,
                'dueDate' => $dueDate,
                'termsAndConditions' => $request->input('termsAndConditions') ?? null,
                'orderStatus' => $due > 0 ? 'PENDING' : 'RECEIVED',
                'customerId' => $request->input('customerId'),
                'userId' => $request->input('userId'),
                'subtotal' => takeUptoThreeDecimal($subtotal),
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'cgst_amount' => takeUptoThreeDecimal($cgstAmount),
                'sgst_amount' => takeUptoThreeDecimal($sgstAmount),
                'total_gst' => takeUptoThreeDecimal($totalGst),
                'grand_total' => takeUptoThreeDecimal($grandTotal),
                'gst_applicable' => $gstApplicable,
            ]);


            foreach ($request->saleInvoiceProduct as $item) {
                $productFinalAmount = ((int)$item['productQuantity'] * (float)$item['productUnitSalePrice']) - (float)$item['productDiscount'];

                $taxAmount = ($productFinalAmount * (float)$item['tax']) / 100;

                // Check if it's a ready product stock item
                $isReadyProduct = false;
                $stockItemId = null;
                
                if (strpos($item['productId'], 'ready_') === 0) {
                    $isReadyProduct = true;
                    $stockItemId = str_replace('ready_', '', $item['productId']);
                } else {
                    // Check if numeric ID is a ready product stock item
                    $productId = (int)$item['productId'];
                    $regularProduct = Product::find($productId);
                    if (!$regularProduct && ReadyProductStockItem::find($productId)) {
                        $isReadyProduct = true;
                        $stockItemId = $productId;
                    }
                }
                
                if ($isReadyProduct && $stockItemId) {
                    // Ready product stock item handling
                    SaleInvoiceProduct::create([
                        'invoiceId' => $createdInvoice->id,
                        'productId' => null,
                        'ready_product_stock_item_id' => $stockItemId,
                        'productQuantity' => (int)$item['productQuantity'],
                        'bag' => isset($item['bag']) ? (float)$item['bag'] : 0,
                        'kg' => isset($item['kg']) ? (float)$item['kg'] : 0,
                        'productUnitSalePrice' => takeUptoThreeDecimal((float)$item['productUnitSalePrice']),
                        'productDiscount' => takeUptoThreeDecimal((float)$item['productDiscount']),
                        'productFinalAmount' => takeUptoThreeDecimal($productFinalAmount),
                        'tax' => $item['tax'],
                        'taxAmount' => takeUptoThreeDecimal($taxAmount),
                    ]);
                } else {
                    // Regular product handling
                    SaleInvoiceProduct::create([
                        'invoiceId' => $createdInvoice->id,
                        'productId' => (int)$item['productId'],
                        'productQuantity' => (int)$item['productQuantity'],
                        'bag' => isset($item['bag']) ? (float)$item['bag'] : 0,
                        'kg' => isset($item['kg']) ? (float)$item['kg'] : 0,
                        'productUnitSalePrice' => takeUptoThreeDecimal((float)$item['productUnitSalePrice']),
                        'productDiscount' => takeUptoThreeDecimal((float)$item['productDiscount']),
                        'productFinalAmount' => takeUptoThreeDecimal($productFinalAmount),
                        'tax' => $item['tax'],
                        'taxAmount' => takeUptoThreeDecimal($taxAmount),
                    ]);
                }

                // Deduct stock from ready product stock items
                if ($isReadyProduct && $stockItemId) {
                    $this->deductReadyProductStock(
                        $stockItemId, 
                        (float)$item['productQuantity'], // KG quantity
                        isset($item['bag']) ? (int)$item['bag'] : 0, // Bags
                        isset($item['kg']) ? (float)$item['kg'] : 0 // Additional KG
                    );
                }
            }

            // cost of sales will be created as journal entry
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 9,
                'creditId' => 3,
                'amount' => takeUptoThreeDecimal((float)$totalPurchasePrice),
                'particulars' => "Cost of sales on Sale Invoice #$createdInvoice->id",
                'type' => 'sale',
                'relatedId' => $createdInvoice->id,
            ]);

            // transaction for account receivable of sales
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 4,
                'creditId' => 8,
                'amount' => takeUptoThreeDecimal($totalSalePriceWithDiscount),
                'particulars' => "total sale price with discount on Sale Invoice #$createdInvoice->id",
                'type' => 'sale',
                'relatedId' => $createdInvoice->id,
            ]);

            // transaction for account receivable of vat
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 4,
                'creditId' => 15,
                'amount' => takeUptoThreeDecimal($totalTax),
                'particulars' => "Tax on Sale Invoice #$createdInvoice->id",
                'type' => 'sale',
                'relatedId' => $createdInvoice->id,
            ]);

            // new transactions will be created as journal entry for paid amount
            foreach ($paidAmountArray as $amountData) {
                if (isset($amountData['amount']) && $amountData['amount'] > 0) {
                    Transaction::create([
                        'date' => new DateTime($date),
                        'debitId' => $amountData['paymentType'] ? $amountData['paymentType'] : 1,
                        'creditId' => 4,
                        'amount' => takeUptoThreeDecimal($amountData['amount']),
                        'particulars' => "Payment receive on Sale Invoice #$createdInvoice->id",
                        'type' => 'sale',
                        'relatedId' => $createdInvoice->id,
                    ]);
                }
            }

            // Update customer's current_due_amount with new sale due
            $customer = \App\Models\Customer::find($request->input('customerId'));
            if ($customer) {
                // Get customer's previous due (before this sale)
                $customerPreviousDue = $customer->current_due_amount ?? 0;
                
                // Calculate invoice total with commission and bag
                $invoiceDue = $createdInvoice->dueAmount;
                $commissionValue = (float)$request->input('commissionValue', 0);
                $bagQuantity = (float)$request->input('bagQuantity', 0);
                $bagPrice = (float)$request->input('bagPrice', 0);
                $bagAmount = $bagQuantity * $bagPrice;
                
                // Total invoice due = invoice due + commission + bag
                $totalInvoiceDue = $invoiceDue + $commissionValue + $bagAmount;
                
                // Customer new due = previous due + total invoice due
                $customerNewDue = $customerPreviousDue + $totalInvoiceDue;
                
                // Update sale invoice with customer due info
                $createdInvoice->update([
                    'customer_previous_due' => takeUptoThreeDecimal($customerPreviousDue),
                    'customer_current_due' => takeUptoThreeDecimal($customerNewDue),
                ]);
                
                // Update customer's current_due_amount
                $customer->update(['current_due_amount' => takeUptoThreeDecimal($customerNewDue)]);
            }

            $converted = arrayKeysToCamelCase($createdInvoice->toArray());
            DB::commit();

            return response()->json(['createdInvoice' => $converted], 201);
        } catch (Exception $err) {
            DB::rollBack();
            
            // Check if it's a stock validation error
            if (strpos($err->getMessage(), 'Insufficient') !== false || 
                strpos($err->getMessage(), 'out of stock') !== false) {
                return response()->json([
                    'error' => $err->getMessage(),
                    'type' => 'stock_error'
                ], 400);
            }
            
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // get all the saleInvoice controller method
    public function getAllSaleInvoice(Request $request): JsonResponse
    {
        if ($request->query('query') === 'info') {
            try {
                $aggregation = SaleInvoice::selectRaw('COUNT(id) as id, SUM(profit) as profit')
                    ->where('isHold', 'false')
                    ->first();

                // transaction of the total amount
                $totalAmount = Transaction::where('type', 'sale')
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'sale')
                    ->where(function ($query) {
                        $query->orWhere('creditId', 4);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                    ->where(function ($query) {
                        $query->where('creditId', 4);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->selectRaw('COUNT(id) as id, SUM(amount) as amount')
                    ->first();

                // calculation of due amount
                $totalDueAmount = (($totalAmount->amount - $totalAmountOfReturn->amount) - $totalPaidAmount->amount) + $totalInstantReturnAmount->amount;

                $result = [
                    '_count' => [
                        'id' => $aggregation->id
                    ],
                    '_sum' => [
                        'totalAmount' => takeUptoThreeDecimal($totalAmount->amount),
                        'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                        'paidAmount' => takeUptoThreeDecimal($totalPaidAmount->amount),
                        'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->amount),
                        'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->amount),
                        'profit' => takeUptoThreeDecimal($aggregation->profit),
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());
                $searchKey = $request->query('key');

                $allSaleInvoice = SaleInvoice::where(function($query) use ($searchKey) {
                        $query->where('id', $searchKey)
                              ->orWhereHas('customer', function($q) use ($searchKey) {
                                  $q->where('username', 'LIKE', "%{$searchKey}%")
                                    ->orWhere('phone', 'LIKE', "%{$searchKey}%");
                              });
                    })
                    ->with('saleInvoiceProduct', 'user:id,firstName,lastName,username', 'customer:id,username')
                    ->orderBy('created_at', 'desc')
                    ->where('isHold', 'false')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $total = SaleInvoice::where(function($query) use ($searchKey) {
                        $query->where('id', $searchKey)
                              ->orWhereHas('customer', function($q) use ($searchKey) {
                                  $q->where('username', 'LIKE', "%{$searchKey}%")
                                    ->orWhere('phone', 'LIKE', "%{$searchKey}%");
                              });
                    })
                    ->count();

                $saleInvoicesIds = $allSaleInvoice->pluck('id')->toArray();
                // modify data to actual data of sale invoice's current value by adjusting with transactions and returns
                // transaction of the total amount
                $totalAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->orWhere('creditId', 4);
                    })
                    ->get();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('creditId', 4);
                    })
                    ->get();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // calculate grand total due amount
                $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');


                $allSaleInvoice = $allSaleInvoice->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {

                    $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;

                    // Keep original database paidAmount
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                    // Keep original database dueAmount
                    $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                    $item->customerCurrentDue = $item->customer_current_due;
                    $item->customerPreviousDue = $item->customer_previous_due;
                    return $item;
                });

                $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());
                $totaluomValue = $allSaleInvoice->sum('totaluomValue');
                $totalUnitQuantity = $allSaleInvoice->map(function ($item) {
                    return $item->saleInvoiceProduct->sum('productQuantity');
                })->sum();

                return response()->json([
                    'aggregations' => [
                        '_count' => [
                            'id' => $total,
                        ],
                        '_sum' => [
                            'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                            'paidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                            'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                            'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                            'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                            'profit' => takeUptoThreeDecimal($allSaleInvoice->sum('profit')),
                            'totaluomValue' => $totaluomValue,
                            'totalUnitQuantity' => $totalUnitQuantity,
                        ],
                    ],
                    'getAllSaleInvoice' => $converted,
                    'totalSaleInvoice' => $total,
                ], 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'search-order') {
            try {
                $allOrder = SaleInvoice::where(function ($query) use ($request) {
                    if ($request->has('status')) {
                        $status = $request->query('status');
                        $query->where('orderStatus', 'LIKE', "%$status%");
                    }
                })
                    ->with('saleInvoiceProduct')
                    ->orderBy('created_at', 'desc')
                    ->where('isHold', 'false')
                    ->get();

                $converted = arrayKeysToCamelCase($allOrder->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'report') {
            try {
                $allOrder = SaleInvoice::with('saleInvoiceProduct', 'user:id,firstName,lastName,username', 'customer:id,username', 'saleInvoiceProduct.product:id,name')
                    ->where('isHold', 'false')
                    ->orderBy('created_at', 'desc')
                    ->when($request->query('salePersonId'), function ($query) use ($request) {
                        return $query->where('userId', $request->query('salePersonId'));
                    })
                    ->when($request->query('customerId'), function ($query) use ($request) {
                        return $query->where('customerId', $request->query('customerId'));
                    })
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                            ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->get();

                $saleInvoicesIds = $allOrder->pluck('id')->toArray();
                // modify data to actual data of sale invoice's current value by adjusting with transactions and returns
                $totalAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->orWhere('creditId', 4);
                    })
                    ->get();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('creditId', 4);
                    })
                    ->get();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // calculate grand total due amount
                $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

                // calculate paid amount and due amount of individual sale invoice from transactions and returnSaleInvoice and attach it to saleInvoices
                $allSaleInvoice = $allOrder->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {

                    $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;


                    // Keep original database paidAmount
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                    // Keep original database dueAmount
                    $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                    $item->customerCurrentDue = $item->customer_current_due;
                    $item->customerPreviousDue = $item->customer_previous_due;
                    return $item;
                });

                $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());
                $totaluomValue = $allSaleInvoice->sum('totaluomValue');
                $totalUnitQuantity = $allSaleInvoice->map(function ($item) {
                    return $item->saleInvoiceProduct->sum('productQuantity');
                })->sum();


                $counted = $allOrder->count();
                return response()->json([
                    'aggregations' => [
                        '_count' => [
                            'id' => $counted,
                        ],
                        '_sum' => [
                            'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                            'paidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                            'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                            'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                            'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                            'profit' => takeUptoThreeDecimal($allSaleInvoice->sum('profit')),
                            'totaluomValue' => $totaluomValue,
                            'totalUnitQuantity' => $totalUnitQuantity,
                        ],
                    ],
                    'getAllSaleInvoice' => $converted,
                    'totalSaleInvoice' => $counted,
                ], 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());

                $allOrder = SaleInvoice::with(['saleInvoiceProduct', 'user:id,firstName,lastName,username', 'customer:id,username,last_due_amount'])
                    ->where('isHold', 'false')
                    ->orderBy('created_at', 'desc')
                    ->when($request->query('key'), function ($query) use ($request) {
                        $searchKey = $request->query('key');
                        return $query->where(function($q) use ($searchKey) {
                            $q->where('id', $searchKey)
                              ->orWhere('invoiceMemoNo', 'LIKE', "%{$searchKey}%")
                              ->orWhereHas('customer', function($customerQuery) use ($searchKey) {
                                  $customerQuery->where('username', 'LIKE', "%{$searchKey}%")
                                                ->orWhere('phone', 'LIKE', "%{$searchKey}%");
                              });
                        });
                    })
                    ->when($request->query('salePersonId'), function ($query) use ($request) {
                        return $query->whereIn('userId', explode(',', $request->query('salePersonId')));
                    })
                    ->when($request->query('orderStatus'), function ($query) use ($request) {
                        return $query->whereIn('orderStatus', explode(',', $request->query('orderStatus')));
                    })
                    ->when($request->query('customerId'), function ($query) use ($request) {
                        return $query->whereIn('customerId', explode(',', $request->query('customerId')));
                    })
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                            ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $saleInvoicesIds = $allOrder->pluck('id')->toArray();
                // modify data to actual data of sale invoice's current value by adjusting with transactions and returns
                $totalAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4)
                            ->where('creditId', 8);
                    })
                    ->get();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->orWhere('creditId', 4);
                    })
                    ->get();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('creditId', 4);
                    })
                    ->get();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // calculate grand total due amount
                $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

                // calculate paid amount and due amount of individual sale invoice from transactions and returnSaleInvoice and attach it to saleInvoices
                $allSaleInvoice = $allOrder->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {

                    $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;


                    // Keep original database paidAmount
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                    // Keep original database dueAmount
                    $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                    $item->customerCurrentDue = $item->customer_current_due;
                    $item->customerPreviousDue = $item->customer_previous_due;
                    return $item;
                });

                $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());
                $totaluomValue = $allSaleInvoice->sum('totaluomValue');
                $totalUnitQuantity = $allSaleInvoice->map(function ($item) {
                    return $item->saleInvoiceProduct->sum('productQuantity');
                })->sum();


                $counted = $allOrder->count();
                return response()->json([
                    'aggregations' => [
                        '_count' => [
                            'id' => $counted,
                        ],
                        '_sum' => [
                            'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                            'paidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                            'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                            'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                            'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                            'profit' => takeUptoThreeDecimal($allSaleInvoice->sum('profit')),
                            'totaluomValue' => $totaluomValue,
                            'totalUnitQuantity' => $totalUnitQuantity,
                        ],
                    ],
                    'getAllSaleInvoice' => $converted,
                    'totalSaleInvoice' => $counted,
                ], 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'invalid query!'], 400);
        }
    }

    // get a single saleInvoice controller method
    public function getSingleSaleInvoice($id): JsonResponse
    {
        try {
            // get single Sale invoice information with products
            $singleSaleInvoice = SaleInvoice::where('id', $id)
                ->with(['saleInvoiceProduct', 'saleInvoiceProduct' => function ($query) {
                    $query->with(['product', 'readyProductStockItem' => function($q) {
                        $q->select('id', 'sale_product_name', 'ready_product_name', 'unit_price')
                          ->with(['saleProduct:id,name']);
                    }])->orderBy('id', 'desc');
                }, 'customer:id,username,address,phone,email,current_due_amount', 'user:id,firstName,lastName,username'])
                ->where('isHold', 'false')
                ->first();

            if (!$singleSaleInvoice) {
                return response()->json(['error' => 'This invoice not Found'], 400);
            }


            // transaction of the total amount
            $totalAmount = Transaction::where('type', 'sale')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the paidAmount
            $totalPaidAmount = Transaction::where('type', 'sale')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->orWhere('creditId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the total amount
            $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('creditId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the total instant return
            $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // calculation of due amount
            $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

            // get all transactions related to this sale invoice
            $transactions = Transaction::where('relatedId', $id)
                ->where(function ($query) {
                    $query->orWhere('type', 'sale')
                        ->orWhere('type', 'sale_return');
                })
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            // get totalReturnAmount of saleInvoice
            $returnSaleInvoice = ReturnSaleInvoice::where('saleInvoiceId', $id)
                ->with('returnSaleInvoiceProduct', 'returnSaleInvoiceProduct.product')
                ->orderBy('id', 'desc')
                ->get();

            $status = 'UNPAID';
            if ($totalDueAmount <= 0.0) {
                $status = "PAID";
            }

            // calculate total uomValue
            $totaluomValue = $singleSaleInvoice->saleInvoiceProduct->reduce(function ($acc, $item) {
                if ($item->product && $item->product->uomValue) {
                    return $acc + (int)$item->product->uomValue * $item->productQuantity;
                }
                // For ready product stock items, use default uomValue of 1
                return $acc + 1 * $item->productQuantity;
            }, 0);


            // Add customer current due amount to sale invoice data
            $singleSaleInvoiceArray = $singleSaleInvoice->toArray();
            if ($singleSaleInvoice->customer) {
                $singleSaleInvoiceArray['customerCurrentDue'] = $singleSaleInvoice->customer->current_due_amount;
                // Add customer previous due (excluding this invoice)
                $customerPreviousDue = ($singleSaleInvoice->customer->current_due_amount ?? 0) - ($singleSaleInvoice->dueAmount ?? 0);
                $singleSaleInvoiceArray['customerPreviousDue'] = max(0, $customerPreviousDue);
            }
            
            $convertedSingleSaleInvoice = arrayKeysToCamelCase($singleSaleInvoiceArray);
            $convertedReturnSaleInvoice = arrayKeysToCamelCase($returnSaleInvoice->toArray());
            $convertedTransactions = arrayKeysToCamelCase($transactions->toArray());

            $finalResult = [
                'status' => $status,
                'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                'totalPaidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                'totaluomValue' => $totaluomValue,
                'singleSaleInvoice' => $convertedSingleSaleInvoice,
                'returnSaleInvoice' => $convertedReturnSaleInvoice,
                'transactions' => $convertedTransactions,
            ];

            return response()->json($finalResult, 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // update single sale invoice with stock adjustment
    public function updateSingleSaleInvoice(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $saleInvoice = SaleInvoice::with('saleInvoiceProduct')->find($id);
            
            if (!$saleInvoice) {
                return response()->json(['error' => 'Sale Invoice not found!'], 404);
            }

            $stockAdjustments = $request->input('stockAdjustments', []);
            $saleInvoiceProducts = $request->input('saleInvoiceProduct', []);

            // Process stock adjustments
            foreach ($stockAdjustments as $adjustment) {
                if (!$adjustment['hasChange']) continue;

                $bagAdjustment = (float)$adjustment['bagAdjustment'];
                $quantityAdjustment = (float)$adjustment['quantityAdjustment'];

                // Adjust ready product stock
                if (isset($adjustment['readyProductStockItemId']) && $adjustment['readyProductStockItemId']) {
                    $stockItem = ReadyProductStockItem::find($adjustment['readyProductStockItemId']);
                    if ($stockItem) {
                        // Add back to stock (positive adjustment means adding back)
                        $stockItem->current_stock_bags += $bagAdjustment;
                        $stockItem->current_stock_kg += $quantityAdjustment;
                        
                        // Recalculate remaining kg
                        $bagsWeightKg = $stockItem->bags_weight_kg > 0 ? $stockItem->bags_weight_kg : 0;
                        $totalBagWeight = $stockItem->current_stock_bags * $bagsWeightKg;
                        $stockItem->remaining_kg = $stockItem->current_stock_kg - $totalBagWeight;
                        
                        $stockItem->save();
                    }
                }

                // Adjust regular product stock
                if (isset($adjustment['productId']) && $adjustment['productId'] && !isset($adjustment['readyProductStockItemId'])) {
                    $product = Product::find($adjustment['productId']);
                    if ($product) {
                        // Add back to stock (positive adjustment means adding back)
                        $newBags = ($product->current_bags ?? 0) + $bagAdjustment;
                        $newStockKg = ($product->current_stock_kg ?? 0) + $quantityAdjustment;
                        
                        // Ensure stock doesn't go negative
                        $product->current_bags = max(0, $newBags);
                        $product->current_stock_kg = max(0, $newStockKg);
                        $product->save();
                    }
                }
            }

            // Update sale invoice products
            foreach ($saleInvoiceProducts as $productData) {
                $saleProduct = SaleInvoiceProduct::find($productData['id']);
                if ($saleProduct) {
                    // Calculate fresh productFinalAmount
                    $productFinalAmount = ((float)$productData['productQuantity'] * (float)$productData['productUnitSalePrice']) - (float)($productData['productDiscount'] ?? 0);
                    $taxAmount = ($productFinalAmount * (float)($productData['tax'] ?? 0)) / 100;
                    
                    $saleProduct->update([
                        'bag' => $productData['bag'],
                        'kg' => $productData['kg'],
                        'productQuantity' => $productData['productQuantity'],
                        'productUnitSalePrice' => $productData['productUnitSalePrice'],
                        'productDiscount' => $productData['productDiscount'] ?? 0,
                        'productFinalAmount' => takeUptoThreeDecimal($productFinalAmount),
                        'tax' => $productData['tax'] ?? 0,
                        'taxAmount' => takeUptoThreeDecimal($taxAmount)
                    ]);
                }
            }

            // Calculate fresh totals (not adding to existing)
            $totalAmount = 0;
            $totalDiscount = 0;
            $totalTax = 0;
            foreach ($saleInvoiceProducts as $item) {
                // Calculate fresh product final amount
                $productFinalAmount = ((float)$item['productQuantity'] * (float)$item['productUnitSalePrice']) - (float)($item['productDiscount'] ?? 0);
                $totalAmount += $productFinalAmount;
                $totalDiscount += (float)($item['productDiscount'] ?? 0);
                $taxAmount = ($productFinalAmount * (float)($item['tax'] ?? 0)) / 100;
                $totalTax += $taxAmount;
            }

            // GST Calculation
            $gstApplicable = $request->input('gstApplicable', false);
            $cgstRate = $request->input('cgstRate', 2.5);
            $sgstRate = $request->input('sgstRate', 2.5);
            
            $subtotal = $totalAmount;
            $cgstAmount = 0;
            $sgstAmount = 0;
            $totalGst = 0;
            $grandTotal = $subtotal;
            
            if ($gstApplicable) {
                $cgstAmount = ($subtotal * $cgstRate) / 100;
                $sgstAmount = ($subtotal * $sgstRate) / 100;
                $totalGst = $cgstAmount + $sgstAmount;
                $grandTotal = $subtotal + $totalGst;
            }

            // Simple date handling
            $dateInput = $request->input('date');
            $dueDateInput = $request->input('dueDate');
            
            $date = null;
            $dueDate = null;
            
            if ($dateInput) {
                try {
                    $date = Carbon::parse($dateInput)->format('Y-m-d');
                } catch (Exception $e) {
                    $date = $dateInput; // Keep original if parsing fails
                }
            }
            
            if ($dueDateInput) {
                try {
                    $dueDate = Carbon::parse($dueDateInput)->format('Y-m-d');
                } catch (Exception $e) {
                    $dueDate = $dueDateInput; // Keep original if parsing fails
                }
            }

            // Calculate due amount properly
            $paidAmount = $saleInvoice->paidAmount ?? 0;
            $newDueAmount = $grandTotal - $paidAmount;
            
            // Get customer info for due calculation
            $customer = \App\Models\Customer::find($request->input('customerId'));
            if (!$customer) {
                return response()->json(['error' => 'Customer not found!'], 404);
            }
            
            // Get the original customer previous due from the sale invoice
            $originalCustomerPreviousDue = $saleInvoice->customer_previous_due ?? 0;
            $oldInvoiceDue = $saleInvoice->dueAmount ?? 0;
            
            // Calculate customer's current due by removing the old invoice impact
            $customerCurrentDueWithoutThisInvoice = ($customer->current_due_amount ?? 0) - $oldInvoiceDue;
            
            // New customer due = previous due (without this invoice) + new invoice due
            $customerNewDue = $customerCurrentDueWithoutThisInvoice + $newDueAmount;
            
            // Calculate total_calculation for update
            $commissionValue = (float)$request->input('commissionValue', 0);
            $bagQuantity = (float)$request->input('bagQuantity', 0);
            $bagPrice = (float)$request->input('bagPrice', 0);
            $bagAmount = $bagQuantity * $bagPrice;
            $totalCalculation = $totalAmount + $commissionValue + $bagAmount + $customerCurrentDueWithoutThisInvoice;
            
            // Update sale invoice
            $saleInvoice->update([
                'customerId' => $request->input('customerId'),
                'date' => $date,
                'dueDate' => $dueDate,
                'userId' => $request->input('userId'),
                'address' => $request->input('address'),
                'commission_type' => $request->input('commissionType'),
                'commission_value' => $request->input('commissionValue') ? takeUptoThreeDecimal((float)$request->input('commissionValue')) : 0,
                'bag_quantity' => $request->input('bagQuantity') ? takeUptoThreeDecimal((float)$request->input('bagQuantity')) : 0,
                'bag_price' => $request->input('bagPrice') ? takeUptoThreeDecimal((float)$request->input('bagPrice')) : 0,
                'note' => $request->input('note'),
                'termsAndConditions' => $request->input('termsAndConditions'),
                'totalAmount' => takeUptoThreeDecimal($totalAmount),
                'totalDiscountAmount' => takeUptoThreeDecimal($totalDiscount),
                'totalTaxAmount' => takeUptoThreeDecimal($totalTax),
                'dueAmount' => takeUptoThreeDecimal($newDueAmount),
                'subtotal' => takeUptoThreeDecimal($subtotal),
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'cgst_amount' => takeUptoThreeDecimal($cgstAmount),
                'sgst_amount' => takeUptoThreeDecimal($sgstAmount),
                'total_gst' => takeUptoThreeDecimal($totalGst),
                'grand_total' => takeUptoThreeDecimal($grandTotal),
                'gst_applicable' => $gstApplicable,
                'customer_previous_due' => takeUptoThreeDecimal($customerCurrentDueWithoutThisInvoice),
                'customer_current_due' => takeUptoThreeDecimal($customerNewDue),
            ]);
            
            // Update customer's current_due_amount
            if ($customer) {
                $customer->update(['current_due_amount' => takeUptoThreeDecimal($customerNewDue)]);
            }

            DB::commit();

            // Return updated sale data
            $updatedSale = $this->getSingleSaleInvoice($id);
            return $updatedSale;

        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // update saleInvoice controller method
    public function updateSaleStatus(Request $request): JsonResponse
    {
        try {

            $saleInvoice = SaleInvoice::where('id', $request->input('invoiceId'))->first();

            if (!$saleInvoice) {
                return response()->json(['error' => 'SaleInvoice not Found!'], 404);
            }

            $saleInvoice->update([
                'orderStatus' => $request->input('orderStatus'),
            ]);

            return response()->json(['message' => 'Sale Invoice updated successfully!'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // get all the saleInvoice controller method
    public function getAllSaleInvoiceByCustomer(Request $request): JsonResponse
    {

        $data = $request->attributes->get('data');
        
        if($data['role'] != 'customer' || !$data['sub']) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        

           if ($request->query()) {
              try {

                $customerId = $data['sub'];
                  $pagination = getPagination($request->query());
  
                  $allOrder = SaleInvoice::with('saleInvoiceProduct.product', 'customer:id,username')
                  ->where('customerId', $customerId)
                      ->where('isHold', 'false')
                      ->orderBy('created_at', 'desc')
                      ->when($request->query('orderStatus'), function ($query) use ($request) {
                          return $query->whereIn('orderStatus', explode(',', $request->query('orderStatus')));
                      })
                      ->skip($pagination['skip'])
                      ->take($pagination['limit'])
                      ->get();

                $totalOrder = SaleInvoice::with('saleInvoiceProduct', 'customer:id,username')
                ->where('customerId', $customerId)
                    ->where('isHold', 'false')
                    ->orderBy('created_at', 'desc')
                    ->when($request->query('orderStatus'), function ($query) use ($request) {
                        return $query->whereIn('orderStatus', explode(',', $request->query('orderStatus')));
                    })
                    ->count();
  
                  $saleInvoicesIds = $allOrder->pluck('id')->toArray();
                  // modify data to actual data of sale invoice's current value by adjusting with transactions and returns
                  $totalAmount = Transaction::where('type', 'sale')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->where('debitId', 4)
                              ->where('creditId', 8);
                      })
                      ->get();
  
                  // transaction of the paidAmount
                  $totalPaidAmount = Transaction::where('type', 'sale')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->orWhere('creditId', 4);
                      })
                      ->get();
  
                  // transaction of the total amount
                  $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->where('creditId', 4);
                      })
                      ->get();
  
                  // transaction of the total instant return
                  $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->where('debitId', 4);
                      })
                      ->get();
  
                  // calculate paid amount and due amount of individual sale invoice from transactions and returnSaleInvoice and attach it to saleInvoices
                  $allSaleInvoice = $allOrder->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {
  
                      $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;
  
  
                      // Keep original database paidAmount
                      $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                      // Keep original database dueAmount
                      $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                      return $item;
                  });
  
                  $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());

                  return response()->json([
                      'getAllSaleInvoice' => $converted,
                      'totalSaleInvoice' => $totalOrder,
                  ], 200);
              } catch (Exception $err) {
                  return response()->json(['error' => $err->getMessage()], 500);
              }
          } else {
              return response()->json(['error' => 'invalid query!'], 400);
          }
    }
      // get a single saleInvoice controller method
      public function getSingleSaleInvoiceForCustomer(Request $request, $id): JsonResponse
      {
          try {

            $data = $request->attributes->get('data');
        
            if($data['role'] != 'customer' || !$data['sub']) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
              // get single Sale invoice information with products
              $singleSaleInvoice = SaleInvoice::where('id', $id)
              ->where('customerId', $data['sub'])
                  ->with(['saleInvoiceProduct', 'saleInvoiceProduct' => function ($query) {
                      $query->with(['product', 'readyProductStockItem' => function($q) {
                          $q->select('id', 'sale_product_name', 'ready_product_name', 'unit_price')
                            ->with(['saleProduct:id,name']);
                      }])->orderBy('id', 'desc');
                  }, 'customer:id,username,address,phone,email,current_due_amount', 'user:id,firstName,lastName,username'])
                  ->where('isHold', 'false')
                  ->first();
  
              if (!$singleSaleInvoice) {
                  return response()->json(['error' => 'This invoice not Found'], 400);
              }
  
  
              // transaction of the total amount
              $totalAmount = Transaction::where('type', 'sale')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->where('debitId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // transaction of the paidAmount
              $totalPaidAmount = Transaction::where('type', 'sale')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->orWhere('creditId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // transaction of the total amount
              $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->where('creditId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // transaction of the total instant return
              $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->where('debitId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // calculation of due amount
              $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');
  
              // get all transactions related to this sale invoice
              $transactions = Transaction::where('relatedId', $id)
                  ->where(function ($query) {
                      $query->orWhere('type', 'sale')
                          ->orWhere('type', 'sale_return');
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->orderBy('id', 'desc')
                  ->get();
  
              // get totalReturnAmount of saleInvoice
              $returnSaleInvoice = ReturnSaleInvoice::where('saleInvoiceId', $id)
                  ->with('returnSaleInvoiceProduct', 'returnSaleInvoiceProduct.product')
                  ->orderBy('id', 'desc')
                  ->get();
  
              $status = 'UNPAID';
              if ($totalDueAmount <= 0.0) {
                  $status = "PAID";
              }
  
              // calculate total uomValue
              $totaluomValue = $singleSaleInvoice->saleInvoiceProduct->reduce(function ($acc, $item) {
                  if ($item->product && $item->product->uomValue) {
                      return $acc + (int)$item->product->uomValue * $item->productQuantity;
                  }
                  // For ready product stock items, use default uomValue of 1
                  return $acc + 1 * $item->productQuantity;
              }, 0);
  
  
              // Add customer current due amount to sale invoice data
              $singleSaleInvoiceArray = $singleSaleInvoice->toArray();
              if ($singleSaleInvoice->customer) {
                  $singleSaleInvoiceArray['customerCurrentDue'] = $singleSaleInvoice->customer->current_due_amount;
              }
              
              $convertedSingleSaleInvoice = arrayKeysToCamelCase($singleSaleInvoiceArray);
              $convertedReturnSaleInvoice = arrayKeysToCamelCase($returnSaleInvoice->toArray());
              $convertedTransactions = arrayKeysToCamelCase($transactions->toArray());
  
              $finalResult = [
                  'status' => $status,
                  'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                  'totalPaidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                  'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                  'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                  'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                  'totaluomValue' => $totaluomValue,
                  'singleSaleInvoice' => $convertedSingleSaleInvoice,
                  'returnSaleInvoice' => $convertedReturnSaleInvoice,
                  'transactions' => $convertedTransactions,
              ];
  
              return response()->json($finalResult, 200);
          } catch (Exception $err) {
              return response()->json(['error' => $err->getMessage()], 500);
          }
      }

    /**
     * Deduct stock from ready product stock items
     */
    private function deductReadyProductStock($stockItemId, $quantityToDeduct, $bagsToDeduct = 0, $additionalKg = 0)
    {
        $stockItem = ReadyProductStockItem::find($stockItemId);
        
        if (!$stockItem) {
            throw new \Exception("Stock item not found for ID: {$stockItemId}");
        }
        
        // Calculate total KG to deduct
        // quantityToDeduct is the main quantity, additionalKg is from 'kg' field
        $totalKgToDeduct = $quantityToDeduct;
        
        // Check if product is out of stock
        if ($stockItem->current_stock_kg <= 0) {
            $productName = $stockItem->saleProduct->name ?? $stockItem->ready_product_name ?? 'Ready Product';
            throw new \Exception("Product '{$productName}' is out of stock now!");
        }
        
        // Check if sufficient stock is available
        if ($totalKgToDeduct > 0 && $stockItem->current_stock_kg < $totalKgToDeduct) {
            $productName = $stockItem->saleProduct->name ?? $stockItem->ready_product_name ?? 'Ready Product';
            throw new \Exception("Insufficient stock for '{$productName}'! Available: {$stockItem->current_stock_kg} kg, Required: {$totalKgToDeduct} kg");
        }
        
        if ($bagsToDeduct > 0 && $stockItem->current_stock_bags < $bagsToDeduct) {
            $productName = $stockItem->saleProduct->name ?? $stockItem->ready_product_name ?? 'Ready Product';
            throw new \Exception("Insufficient bags for '{$productName}'! Available: {$stockItem->current_stock_bags} bags, Required: {$bagsToDeduct} bags");
        }
        
        // Deduct the stock
        $newCurrentStockKg = $stockItem->current_stock_kg - $totalKgToDeduct;
        $newCurrentStockBags = $stockItem->current_stock_bags;
        
        if ($bagsToDeduct > 0) {
            $newCurrentStockBags -= $bagsToDeduct;
        }
        
        // Update the stock values
        $stockItem->current_stock_kg = $newCurrentStockKg;
        $stockItem->current_stock_bags = $newCurrentStockBags;
        
        // Update remaining kg calculation
        $bagsWeightKg = $stockItem->bags_weight_kg > 0 ? $stockItem->bags_weight_kg : 0;
        $totalBagWeight = $stockItem->current_stock_bags * $bagsWeightKg;
        $stockItem->remaining_kg = $stockItem->current_stock_kg - $totalBagWeight;
        
        $stockItem->save();
        
        return true;
    }
}
