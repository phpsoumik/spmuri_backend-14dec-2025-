<?php

namespace App\Http\Controllers;

use App\Models\PurchaseProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PurchaseProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = PurchaseProduct::query();

            $page = $request->get('page', 1);
            $count = $request->get('count', 10);
            
            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            
            if ($request->has('sku')) {
                $query->where('sku', 'like', '%' . $request->sku . '%');
            }

            // No need to filter deleted_at since we're not using it
            
            if ($request->has('query') && $request->query === 'all') {
                // Get all products including inactive ones
                $purchaseProducts = $query->get();
            } else {
                // Show all products by default (both active and inactive)
                $purchaseProducts = $query->paginate($count, ['*'], 'page', $page);
            }

            return response()->json([
                'message' => 'success',
                'data' => $purchaseProducts
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'purchase_price' => 'nullable|numeric|min:0',
                'sku' => 'nullable|string|unique:purchase_products,sku',
                'description' => 'nullable|string'
            ]);

            $purchaseProduct = PurchaseProduct::create($request->all());

            return response()->json([
                'message' => 'success',
                'data' => $purchaseProduct
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $purchaseProduct = PurchaseProduct::findOrFail($id);

            return response()->json([
                'message' => 'success',
                'data' => $purchaseProduct
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $purchaseProduct = PurchaseProduct::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'purchase_price' => 'nullable|numeric|min:0',
                'sku' => 'nullable|string|unique:purchase_products,sku,' . $id,
                'description' => 'nullable|string'
            ]);

            $purchaseProduct->update($request->all());

            return response()->json([
                'message' => 'success',
                'data' => $purchaseProduct
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $purchaseProduct = PurchaseProduct::findOrFail($id);
            
            // With CASCADE DELETE, this should work now
            $purchaseProduct->delete();
            
            return response()->json([
                'message' => 'success',
                'action' => 'deleted'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}