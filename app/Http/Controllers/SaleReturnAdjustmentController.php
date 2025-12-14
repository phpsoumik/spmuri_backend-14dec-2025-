<?php

namespace App\Http\Controllers;

use App\Models\SaleReturnAdjustment;
use App\Models\ReturnSaleInvoice;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleReturnAdjustmentController extends Controller
{
    // Create adjustment
    public function createAdjustment(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validate = Validator::make($request->all(), [
                'return_sale_invoice_id' => 'required|string|exists:returnsaleinvoice,id',
                'adjustment_type' => 'required|in:cash_refund,product_exchange',
                'cash_refund_amount' => 'nullable|numeric|min:0',
                'exchange_product_id' => 'nullable|exists:product,id',
                'exchange_quantity' => 'nullable|integer|min:0',
                'exchange_bag' => 'nullable|numeric|min:0',
                'exchange_kg' => 'nullable|numeric|min:0',
            ]);

            if ($validate->fails()) {
                return response()->json(['error' => $validate->errors()->first()], 400);
            }

            $adjustment = SaleReturnAdjustment::create([
                'return_sale_invoice_id' => $request->return_sale_invoice_id,
                'adjustment_type' => $request->adjustment_type,
                'cash_refund_amount' => $request->cash_refund_amount ?? 0,
                'exchange_product_id' => $request->exchange_product_id,
                'exchange_quantity' => $request->exchange_quantity ?? 0,
                'exchange_bag' => $request->exchange_bag ?? 0,
                'exchange_kg' => $request->exchange_kg ?? 0,
                'notes' => $request->notes
            ]);

            if ($request->adjustment_type === 'cash_refund' && $request->cash_refund_amount > 0) {
                // Create cash refund transaction
                Transaction::create([
                    'date' => new DateTime(),
                    'debitId' => 4, // Account Receivable
                    'creditId' => 1, // Cash
                    'amount' => takeUptoThreeDecimal($request->cash_refund_amount),
                    'particulars' => "Cash refund for return adjustment #{$adjustment->id}",
                    'type' => 'adjustment',
                    'relatedId' => $request->return_sale_invoice_id,
                ]);
            } elseif ($request->adjustment_type === 'product_exchange' && $request->exchange_product_id) {
                // Decrease exchange product quantity
                Product::where('id', $request->exchange_product_id)
                    ->decrement('productQuantity', $request->exchange_quantity ?? 0);
            }

            DB::commit();
            $converted = arrayKeysToCamelCase($adjustment->toArray());
            return response()->json(['message' => 'success', 'data' => $converted], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get all adjustments
    public function getAllAdjustments(Request $request): JsonResponse
    {
        try {
            $pagination = getPagination($request->query());
            
            $adjustments = SaleReturnAdjustment::with([
                'returnSaleInvoice.saleInvoice.customer:id,username',
                'exchangeProduct:id,name'
            ])
            ->where('status', true)
            ->orderBy('created_at', 'desc')
            ->skip($pagination['skip'])
            ->take($pagination['limit'])
            ->get();

            $total = SaleReturnAdjustment::where('status', true)->count();

            $converted = arrayKeysToCamelCase($adjustments->toArray());
            return response()->json([
                'adjustments' => $converted,
                'total' => $total
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get single adjustment
    public function getSingleAdjustment($id): JsonResponse
    {
        try {
            $adjustment = SaleReturnAdjustment::with([
                'returnSaleInvoice.saleInvoice.customer',
                'exchangeProduct'
            ])->find($id);

            if (!$adjustment) {
                return response()->json(['error' => 'Adjustment not found'], 404);
            }

            $converted = arrayKeysToCamelCase($adjustment->toArray());
            return response()->json($converted, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Delete adjustment
    public function deleteAdjustment($id): JsonResponse
    {
        try {
            $adjustment = SaleReturnAdjustment::find($id);
            
            if (!$adjustment) {
                return response()->json(['error' => 'Adjustment not found'], 404);
            }

            $adjustment->update(['status' => false]);
            return response()->json(['message' => 'Adjustment deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}