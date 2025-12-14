<?php

namespace App\Http\Controllers;

use App\Models\ReadyProductStock;
use App\Models\ReadyProductStockItem;
use App\Models\PurchaseProduct;
use App\Models\PurchaseInvoiceProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReadyProductStockController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ReadyProductStock::with([
                'items.rawMaterial:id,name', 
                'items.saleProduct:id,name',
                'items' => function($q) {
                    $q->select('*') // Include all fields
                      ->where('current_stock_kg', '>', 0); // Only show items with stock
                }
            ]);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            }

            $stocks = $query->orderBy('created_at', 'desc')->paginate(10);
            
            // Convert to array and fix date format
            $stocksArray = $stocks->toArray();
            foreach ($stocksArray['data'] as &$stock) {
                if (isset($stock['date'])) {
                    // Extract just the date part without timezone conversion
                    $stock['date'] = substr($stock['date'], 0, 10);
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $stocksArray
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ready product stocks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'readyProductStock' => 'required|array|min:1',
            'readyProductStock.*.rawMaterialId' => 'required|integer',
            'readyProductStock.*.rawQuantity' => 'required|numeric|min:0',
            'readyProductStock.*.readyQuantityKg' => 'required|numeric|min:0',
            'readyProductStock.*.unitPrice' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            // Fix date format
            $date = $request->date;
            if (strpos($date, 'T') !== false) {
                $date = date('Y-m-d', strtotime($date));
            }
            
            $stock = ReadyProductStock::create([
                'date' => $date,
                'reference' => $request->reference,
                'note' => $request->note,
                'total_amount' => $request->totalAmount ?? 0,
                'total_ready_product_kg' => $request->totalReadyProductKg ?? 0,
                'total_bags' => $request->totalBags ?? 0,
                'status' => 'completed'
            ]);

            foreach ($request->readyProductStock as $item) {
                // Create ready product stock item
                ReadyProductStockItem::create([
                    'ready_product_stock_id' => $stock->id,
                    'raw_material_id' => $item['rawMaterialId'],
                    'sale_product_name' => $item['saleProductName'] ?? null,
                    'raw_quantity' => $item['rawQuantity'],
                    'ready_quantity_kg' => $item['readyQuantityKg'],
                    'current_stock_kg' => $item['readyQuantityKg'], // Set initial stock
                    'ready_quantity_bags' => $item['readyQuantityBags'] ?? 0,
                    'current_stock_bags' => $item['readyQuantityBags'] ?? 0, // Set initial stock
                    'bags_weight_kg' => $item['bagsWeightKg'] ?? 0,
                    'remaining_kg' => $item['remainingKg'] ?? 0,
                    'unit_price' => $item['unitPrice'],
                    'total_price' => $item['totalPrice'],
                    'conversion_ratio' => $item['conversionRatio'] ?? 1,
                    'ready_product_name' => $item['readyProductName'] ?? 'Ready Product'
                ]);

                // Deduct raw material stock
                $this->deductRawMaterialStock($item['rawMaterialId'], $item['rawQuantity']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ready product stock created successfully',
                'data' => $stock->load('items.rawMaterial')
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ready product stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $stock = ReadyProductStock::with(['items.rawMaterial'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $stock
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ready product stock not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'readyProductStock' => 'required|array|min:1',
            'readyProductStock.*.rawMaterialId' => 'required|integer',
            'readyProductStock.*.rawQuantity' => 'required|numeric|min:0',
            'readyProductStock.*.readyQuantityKg' => 'required|numeric|min:0',
            'readyProductStock.*.unitPrice' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $stock = ReadyProductStock::findOrFail($id);
            
            // Fix date format
            $date = $request->date;
            if (strpos($date, 'T') !== false) {
                $date = date('Y-m-d', strtotime($date));
            }
            
            // Update main stock record
            $stock->update([
                'date' => $date,
                'reference' => $request->reference,
                'note' => $request->note,
                'total_amount' => $request->totalAmount ?? 0,
                'total_ready_product_kg' => $request->totalReadyProductKg ?? 0,
                'total_bags' => $request->totalBags ?? 0
            ]);

            // Update stock items
            foreach ($request->readyProductStock as $item) {
                if (isset($item['id']) && $item['id']) {
                    // Update existing item
                    $stockItem = ReadyProductStockItem::find($item['id']);
                    if ($stockItem) {
                        $stockItem->update([
                            'raw_material_id' => $item['rawMaterialId'],
                            'sale_product_name' => $item['saleProductName'] ?? null,
                            'raw_quantity' => $item['rawQuantity'],
                            'ready_quantity_kg' => $item['readyQuantityKg'],
                            'current_stock_kg' => $item['currentStockKg'] ?? $item['readyQuantityKg'],
                            'ready_quantity_bags' => $item['readyQuantityBags'] ?? 0,
                            'current_stock_bags' => $item['currentStockBags'] ?? $item['readyQuantityBags'] ?? 0,
                            'bags_weight_kg' => $item['bagsWeightKg'] ?? 0,
                            'remaining_kg' => $item['remainingKg'] ?? 0,
                            'unit_price' => $item['unitPrice'],
                            'total_price' => $item['totalPrice'],
                            'conversion_ratio' => $item['conversionRatio'] ?? 1,
                            'ready_product_name' => $item['readyProductName'] ?? 'Ready Product'
                        ]);
                    }
                } else {
                    // Create new item
                    ReadyProductStockItem::create([
                        'ready_product_stock_id' => $stock->id,
                        'raw_material_id' => $item['rawMaterialId'],
                        'sale_product_name' => $item['saleProductName'] ?? null,
                        'raw_quantity' => $item['rawQuantity'],
                        'ready_quantity_kg' => $item['readyQuantityKg'],
                        'current_stock_kg' => $item['readyQuantityKg'],
                        'ready_quantity_bags' => $item['readyQuantityBags'] ?? 0,
                        'current_stock_bags' => $item['readyQuantityBags'] ?? 0,
                        'bags_weight_kg' => $item['bagsWeightKg'] ?? 0,
                        'remaining_kg' => $item['remainingKg'] ?? 0,
                        'unit_price' => $item['unitPrice'],
                        'total_price' => $item['totalPrice'],
                        'conversion_ratio' => $item['conversionRatio'] ?? 1,
                        'ready_product_name' => $item['readyProductName'] ?? 'Ready Product'
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ready product stock updated successfully',
                'data' => $stock->load('items.rawMaterial')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ready product stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $stock = ReadyProductStock::findOrFail($id);
            $stock->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ready product stock deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ready product stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function renewStock($id)
    {
        try {
            $stock = ReadyProductStock::findOrFail($id);
            
            // Renew stock for all items in this stock record
            foreach ($stock->items as $item) {
                $item->update([
                    'current_stock_kg' => $item->ready_quantity_kg,
                    'current_stock_bags' => $item->ready_quantity_bags
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock renewed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function deductRawMaterialStock($rawMaterialId, $quantityToDeduct)
    {
        // Get purchase invoice products for this raw material (FIFO - First In, First Out)
        $purchaseInvoiceProducts = PurchaseInvoiceProduct::where('productId', $rawMaterialId)
            ->where('productQuantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $remainingToDeduct = $quantityToDeduct;

        foreach ($purchaseInvoiceProducts as $purchaseProduct) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $availableQuantity = $purchaseProduct->productQuantity;
            
            if ($availableQuantity >= $remainingToDeduct) {
                // This purchase product has enough stock
                $purchaseProduct->productQuantity -= $remainingToDeduct;
                $purchaseProduct->save();
                $remainingToDeduct = 0;
            } else {
                // Use all available stock from this purchase product
                $remainingToDeduct -= $availableQuantity;
                $purchaseProduct->productQuantity = 0;
                $purchaseProduct->save();
            }
        }

        if ($remainingToDeduct > 0) {
            throw new \Exception("Insufficient raw material stock. Missing: {$remainingToDeduct} kg");
        }
    }
}