<?php

namespace App\Http\Controllers;

use App\Models\B2BSale;
use App\Models\B2BSaleItem;
use App\Models\B2BCompany;
use App\Models\ReadyProductStockItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class B2BSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = B2BSale::with(['mainCompany', 'subCompany', 'items', 'createdBy']);
            
            if ($request->has('main_company_id')) {
                $query->where('main_company_id', $request->main_company_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            $sales = $query->orderBy('created_at', 'desc')->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $sales
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Debug: Log incoming request data
            \Log::info('=== B2B SALE REQUEST DEBUG ===');
            \Log::info('Request Data:', $request->all());
            
            $validated = $request->validate([
                'date' => 'required|date',
                'order_no' => 'nullable|string|max:50',
                'main_company_id' => 'required|exists:b2b_companies,id',
                'sub_company_id' => 'nullable|exists:b2b_companies,id',
                'vehicle_number' => 'nullable|string|max:20',
                'payment_terms' => 'nullable|in:cash,credit_7,credit_15,credit_30',
                'note' => 'nullable|string',
                'subtotal' => 'required|numeric|min:0',
                'gst_enabled' => 'nullable|boolean',
                'cgst_amount' => 'nullable|numeric|min:0',
                'sgst_amount' => 'nullable|numeric|min:0',
                'total_gst' => 'nullable|numeric|min:0',
                'grand_total' => 'required|numeric|min:0',
                'paid_amount' => 'nullable|numeric|min:0',
                'due_amount' => 'nullable|numeric|min:0',
                'payment_methods' => 'nullable|array',
                'items' => 'required|array|min:1',
                'items.*.ready_product_stock_item_id' => 'required|exists:ready_product_stock_items,id',
                'items.*.product_description' => 'required|string',
                'items.*.quantity_kg' => 'required|numeric|min:0.1',
                'items.*.bags' => 'required|integer|min:1',
                'items.*.rate_per_kg' => 'required|numeric|min:0',
                'items.*.hsn_code' => 'nullable|string|max:20'
            ]);

            // Validate stock availability
            foreach ($validated['items'] as $item) {
                $stockItem = ReadyProductStockItem::find($item['ready_product_stock_item_id']);
                
                if (!$stockItem) {
                    throw new Exception("Stock item not found");
                }
                
                if ($stockItem->current_stock_kg < $item['quantity_kg']) {
                    throw new Exception("Insufficient stock for {$item['product_description']}. Available: {$stockItem->current_stock_kg} kg");
                }
            }

            // Generate invoice number
            $invoiceNo = B2BSale::generateInvoiceNumber();
            
            // Use frontend calculated values instead of recalculating
            $subtotal = $request->input('subtotal', 0);
            $gstEnabled = $request->input('gst_enabled', false);
            $cgstAmount = $request->input('cgst_amount', 0);
            $sgstAmount = $request->input('sgst_amount', 0);
            $totalGst = $request->input('total_gst', 0);
            $grandTotal = $request->input('grand_total', 0);
            $paidAmount = $request->input('paid_amount', 0);
            $dueAmount = $request->input('due_amount', 0);
            
            // Debug: Log received values from frontend
            \Log::info('=== FRONTEND VALUES DEBUG ===');
            \Log::info('Subtotal: ' . $subtotal);
            \Log::info('GST Enabled: ' . ($gstEnabled ? 'true' : 'false'));
            \Log::info('CGST Amount: ' . $cgstAmount);
            \Log::info('SGST Amount: ' . $sgstAmount);
            \Log::info('Total GST: ' . $totalGst);
            \Log::info('Grand Total: ' . $grandTotal);
            \Log::info('Paid Amount: ' . $paidAmount);
            \Log::info('Due Amount: ' . $dueAmount);
            
            // Set due date based on payment terms
            $dueDate = null;
            if ($validated['payment_terms'] === 'credit_7') {
                $dueDate = now()->addDays(7);
            } elseif ($validated['payment_terms'] === 'credit_15') {
                $dueDate = now()->addDays(15);
            } elseif ($validated['payment_terms'] === 'credit_30') {
                $dueDate = now()->addDays(30);
            }

            // Prepare sale data
            $saleData = [
                'date' => $validated['date'],
                'invoice_no' => $invoiceNo,
                'order_no' => $validated['order_no'],
                'main_company_id' => $validated['main_company_id'],
                'sub_company_id' => $validated['sub_company_id'],
                'subtotal' => $subtotal,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'total_gst' => $totalGst,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'vehicle_number' => $validated['vehicle_number'],
                'payment_terms' => $validated['payment_terms'] ?? 'cash',
                'due_date' => $dueDate,
                'note' => $validated['note'],
                'status' => $dueAmount > 0 ? 'pending' : 'completed',
                'created_by' => 1 // TODO: Get from authenticated user
            ];
            
            // Debug: Log sale data before saving
            \Log::info('=== SALE DATA TO SAVE ===');
            \Log::info('Sale Data:', $saleData);
            
            // Create B2B Sale
            $sale = B2BSale::create($saleData);
            
            // Debug: Log saved sale
            \Log::info('=== SAVED SALE ===');
            \Log::info('Saved Sale ID: ' . $sale->id);
            \Log::info('Saved Sale Data:', $sale->toArray());

            // Create sale items and deduct stock
            foreach ($validated['items'] as $item) {
                // Create sale item
                B2BSaleItem::create([
                    'b2b_sale_id' => $sale->id,
                    'ready_product_stock_item_id' => $item['ready_product_stock_item_id'],
                    'product_description' => $item['product_description'],
                    'quantity_kg' => $item['quantity_kg'],
                    'bags' => $item['bags'],
                    'rate_per_kg' => $item['rate_per_kg'],
                    'total_amount' => $item['quantity_kg'] * $item['rate_per_kg'],
                    'hsn_code' => $item['hsn_code'] ?? '19041020'
                ]);

                // Deduct stock
                $stockItem = ReadyProductStockItem::find($item['ready_product_stock_item_id']);
                $stockItem->current_stock_kg -= $item['quantity_kg'];
                $stockItem->current_stock_bags -= $item['bags'];
                
                // Update remaining kg
                $totalBagWeight = $stockItem->current_stock_bags * ($stockItem->bags_weight_kg || 0);
                $stockItem->remaining_kg = $stockItem->current_stock_kg - $totalBagWeight;
                
                $stockItem->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'B2B Sale created successfully',
                'data' => $sale->load(['mainCompany', 'subCompany', 'items'])
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $sale = B2BSale::with([
                'mainCompany', 
                'subCompany', 
                'items.readyProductStockItem',
                'createdBy'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $sale
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }
    }

    public function generateInvoice($id): JsonResponse
    {
        try {
            $sale = B2BSale::with([
                'mainCompany', 
                'subCompany', 
                'items.readyProductStockItem'
            ])->findOrFail($id);

            // Get company settings for header
            $companySettings = \App\Models\AppSetting::first();

            return response()->json([
                'success' => true,
                'data' => [
                    'sale' => $sale,
                    'company_settings' => $companySettings
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $sale = B2BSale::with('items')->findOrFail($id);

            // Restore stock for each item
            foreach ($sale->items as $item) {
                $stockItem = ReadyProductStockItem::find($item->ready_product_stock_item_id);
                if ($stockItem) {
                    $stockItem->current_stock_kg += $item->quantity_kg;
                    $stockItem->current_stock_bags += $item->bags;
                    
                    // Update remaining kg
                    $totalBagWeight = $stockItem->current_stock_bags * ($stockItem->bags_weight_kg || 0);
                    $stockItem->remaining_kg = $stockItem->current_stock_kg - $totalBagWeight;
                    
                    $stockItem->save();
                }
            }

            $sale->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addPayment(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|in:cash,bank,upi',
                'payment_date' => 'required|date',
                'note' => 'nullable|string'
            ]);

            $sale = B2BSale::findOrFail($id);

            if ($validated['amount'] > $sale->due_amount) {
                throw new Exception('Payment amount cannot exceed due amount');
            }

            $sale->paid_amount += $validated['amount'];
            $sale->due_amount -= $validated['amount'];
            
            if ($sale->due_amount <= 0) {
                $sale->status = 'completed';
            }

            $sale->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment added successfully',
                'data' => $sale
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}