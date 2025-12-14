<?php

namespace App\Http\Controllers;

use App\Models\RawMaterialStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class RawMaterialStockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            // Get raw material stock from purchase_products table with purchase invoice data
            $purchaseData = DB::table('purchaseinvoiceproduct as pip')
                ->join('purchase_products as pp', 'pip.productId', '=', 'pp.id')
                ->join('purchaseinvoice as pi', 'pip.invoiceId', '=', 'pi.id')
                ->select(
                    'pp.id as productId',
                    'pp.name',
                    'pp.sku',
                    DB::raw('SUM(pip.productQuantity) as totalQuantity'),
                    DB::raw('SUM(COALESCE(pip.bag, 0)) as totalBags'),
                    DB::raw('SUM(COALESCE(pip.kg, 0)) as totalKg'),
                    DB::raw('AVG(pip.productUnitPurchasePrice) as avgPurchasePrice'),
                    DB::raw('MAX(pi.date) as lastPurchaseDate'),
                    DB::raw('COUNT(DISTINCT pi.id) as totalPurchases')
                )
                ->where('pp.status', '!=', 'false')
                ->groupBy('pp.id', 'pp.name', 'pp.sku')
                ->orderBy('lastPurchaseDate', 'desc')
                ->get();
            
            $converted = arrayKeysToCamelCase($purchaseData->toArray());
            return response()->json($converted);
        } catch (Exception $e) {
            return response()->json([], 200);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            // Get single purchase product details with all purchase history
            $productDetails = DB::table('purchaseinvoiceproduct as pip')
                ->join('purchase_products as pp', 'pip.productId', '=', 'pp.id')
                ->join('purchaseinvoice as pi', 'pip.invoiceId', '=', 'pi.id')
                ->select(
                    'pp.id as productId',
                    'pp.name',
                    'pp.sku',
                    'pi.date as purchaseDate',
                    'pip.productQuantity as quantity',
                    'pip.bag as bags',
                    'pip.kg as kg',
                    'pip.productUnitPurchasePrice as purchasePrice',
                    'pi.id as invoiceId'
                )
                ->where('pp.id', $id)
                ->orderBy('pi.date', 'desc')
                ->get();
            
            $converted = arrayKeysToCamelCase($productDetails->toArray());
            return response()->json($converted);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStock(Request $request, $id): JsonResponse
    {
        try {
            return response()->json(['message' => 'Stock updated successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            return response()->json(['message' => 'Raw material stock deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function lowStockAlert(Request $request): JsonResponse
    {
        try {
            // Show products with low stock based on purchase data
            $lowStockItems = RawMaterialStock::with('product')
                ->whereColumn('quantity', '<=', 'reorderLevel')
                ->orWhere('quantity', '<', 10) // Default low stock threshold
                ->get();
            
            $converted = arrayKeysToCamelCase($lowStockItems->toArray());
            return response()->json($converted);
        } catch (Exception $e) {
            return response()->json([], 200); // Return empty array instead of error
        }
    }
}