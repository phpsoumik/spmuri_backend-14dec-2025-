<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SmallMcProduct;

class SmallMcProductController extends Controller
{
    public function index()
    {
        try {
            $products = SmallMcProduct::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'success',
                'data' => $products->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Hardcore debug
        $allData = $request->all();
        $dateValue = $request->input('date');
        
        error_log('=== SMALL MC PRODUCT DEBUG ===');
        error_log('All request data: ' . json_encode($allData));
        error_log('Date value: ' . var_export($dateValue, true));
        error_log('Date type: ' . gettype($dateValue));
        
        try {
            // Direct DB insert for testing
            $insertData = [
                'item' => $request->input('item'),
                'item_name' => $request->input('item_name'),
                'amount' => $request->input('amount'),
                'date' => $dateValue,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            error_log('Insert data: ' . json_encode($insertData));
            
            $productId = \DB::table('small_mc_products')->insertGetId($insertData);
            $product = \DB::table('small_mc_products')->where('id', $productId)->first();
            
            error_log('Saved product: ' . json_encode($product));
            
            return response()->json([
                'success' => true,
                'message' => 'success',
                'data' => $product
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = SmallMcProduct::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Debug: Log incoming request data
        \Log::info('SmallMcProduct update request:', $request->all());
        
        $request->validate([
            'item' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'nullable|date'
        ]);

        try {
            $product = SmallMcProduct::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            $dateValue = $request->input('date');
            
            // Clean the date value - remove quotes if present
            if ($dateValue) {
                $dateValue = trim($dateValue, '"');
            }
            
            $product->update([
                'item' => $request->input('item'),
                'item_name' => $request->input('item_name'),
                'amount' => $request->input('amount'),
                'date' => $dateValue
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'success',
                'data' => $product->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = SmallMcProduct::find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            $product->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}