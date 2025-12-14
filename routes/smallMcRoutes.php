<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Small M/C Product Routes
Route::get('/small-mc-products', function () {
    try {
        $products = DB::table('small_mc_products')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $products->toArray()
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'error',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::post('/small-mc-products', function (Request $request) {
    try {
        $request->validate([
            'item' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0'
        ]);

        $id = DB::table('small_mc_products')->insertGetId([
            'item' => $request->item,
            'item_name' => $request->item_name,
            'amount' => $request->amount,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $product = DB::table('small_mc_products')->where('id', $id)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $product
        ], 201);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'error',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::put('/small-mc-products/{id}', function (Request $request, $id) {
    try {
        $request->validate([
            'item' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0'
        ]);

        $updated = DB::table('small_mc_products')
            ->where('id', $id)
            ->update([
                'item' => $request->item,
                'item_name' => $request->item_name,
                'amount' => $request->amount,
                'updated_at' => now()
            ]);
        
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        $product = DB::table('small_mc_products')->where('id', $id)->first();
        
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $product
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'error',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::delete('/small-mc-products/{id}', function ($id) {
    try {
        $deleted = DB::table('small_mc_products')->where('id', $id)->delete();
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'success'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});