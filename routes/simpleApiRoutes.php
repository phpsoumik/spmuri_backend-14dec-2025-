<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DailyIncomeController;

// Simple test API without any database dependency
Route::get('/simple-test', function () {
    return response()->json([
        'success' => true,
        'message' => 'Simple API is working!',
        'timestamp' => now(),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ]
    ]);
});

// Simple product count API (without relations)
Route::get('/simple-products', function () {
    try {
        $count = \DB::table('product')->count();
        return response()->json([
            'success' => true,
            'total_products' => $count,
            'message' => 'Product count retrieved successfully'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database connection issue',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Simple users count API
Route::get('/simple-users', function () {
    try {
        $count = \DB::table('users')->count();
        return response()->json([
            'success' => true,
            'total_users' => $count,
            'message' => 'Users count retrieved successfully'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database connection issue',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Get all products with basic info
Route::get('/products', function () {
    try {
        $products = \DB::table('product')
            ->select('id', 'name', 'sku', 'productSalePrice', 'productQuantity', 'status')
            ->where('status', 'true')
            ->limit(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'count' => $products->count(),
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database error',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Get all users with basic info
Route::get('/users', function () {
    try {
        $users = \DB::table('users')
            ->select('id', 'name', 'username', 'email', 'phone', 'status')
            ->where('status', 'true')
            ->limit(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'count' => $users->count(),
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database error',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Get single product by ID
Route::get('/products/{id}', function ($id) {
    try {
        $product = \DB::table('product')
            ->select('id', 'name', 'sku', 'productSalePrice', 'productQuantity', 'description', 'status')
            ->where('id', $id)
            ->first();
            
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
            
        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => 'Product retrieved successfully'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database error',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Get customers
Route::get('/customers', function () {
    try {
        $customers = \DB::table('customer')
            ->select('id', 'name', 'phone', 'email', 'address')
            ->limit(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'count' => $customers->count(),
            'data' => $customers,
            'message' => 'Customers retrieved successfully'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database error',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Test database tables
Route::get('/test-tables', function () {
    try {
        $tables = \DB::select('SHOW TABLES');
        $tableNames = [];
        foreach ($tables as $table) {
            $tableNames[] = array_values((array)$table)[0];
        }
        
        return response()->json([
            'success' => true,
            'tables' => $tableNames
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Get purchase products for raw materials from purchaseinvoiceproduct
Route::get('/purchase-products', function () {
    try {
        $purchaseData = \DB::table('purchaseinvoiceproduct as pip')
            ->join('purchase_products as pp', 'pip.productId', '=', 'pp.id')
            ->select(
                'pp.id',
                'pp.name',
                'pp.sku',
                'pp.purchase_price'
            )
            ->groupBy('pp.id', 'pp.name', 'pp.sku', 'pp.purchase_price')
            ->orderBy('pp.name', 'asc')
            ->get();
            
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $purchaseData
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Database error',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Daily Income Routes - Must be before expense routes to avoid conflicts
Route::get('/daily-income', [App\Http\Controllers\DailyIncomeController::class, 'index']);
Route::post('/daily-income', [App\Http\Controllers\DailyIncomeController::class, 'store']);
Route::get('/daily-income/total', [App\Http\Controllers\DailyIncomeController::class, 'total']);
Route::get('/daily-income/balance', [App\Http\Controllers\DailyIncomeController::class, 'balance']);
Route::post('/daily-income/update', [App\Http\Controllers\DailyIncomeController::class, 'update']);
Route::get('/daily-income/{dailyIncome}', [App\Http\Controllers\DailyIncomeController::class, 'show']);
Route::delete('/daily-income/{dailyIncome}', [App\Http\Controllers\DailyIncomeController::class, 'destroy']);

// Expense Category Routes
Route::get('/expense-categories', [App\Http\Controllers\ExpenseCategoryController::class, 'index']);
Route::post('/expense-categories', [App\Http\Controllers\ExpenseCategoryController::class, 'store']);
Route::put('/expense-categories/{expenseCategory}', [App\Http\Controllers\ExpenseCategoryController::class, 'update']);
Route::delete('/expense-categories/{expenseCategory}', [App\Http\Controllers\ExpenseCategoryController::class, 'destroy']);

// Include expense routes with prefix
Route::prefix('expenses')->group(function () {
    require __DIR__ . '/expenseRoutes.php';
});

// Include fix routes
require __DIR__ . '/fixRoutes.php';

// Sale income total
Route::get('/sale-total-paid', [App\Http\Controllers\SaleIncomeController::class, 'getTotalPaidAmount']);

// Small M/C Product Routes with Database
Route::get('/small-mc-products', function () {
    try {
        $products = \DB::table('small_mc_products')
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

Route::post('/small-mc-products', function (\Illuminate\Http\Request $request) {
    try {
        $request->validate([
            'item' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0'
        ]);

        $id = \DB::table('small_mc_products')->insertGetId([
            'item' => $request->item,
            'item_name' => $request->item_name,
            'amount' => $request->amount,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $product = \DB::table('small_mc_products')->where('id', $id)->first();
        
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

Route::put('/small-mc-products/{id}', function (\Illuminate\Http\Request $request, $id) {
    try {
        $request->validate([
            'item' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0'
        ]);

        $updated = \DB::table('small_mc_products')
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
        
        $product = \DB::table('small_mc_products')->where('id', $id)->first();
        
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
        $deleted = \DB::table('small_mc_products')->where('id', $id)->delete();
        
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

