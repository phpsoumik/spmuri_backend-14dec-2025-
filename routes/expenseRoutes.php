<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;

// Test route
Route::get('/test', function() {
    return response()->json(['message' => 'Expense routes working!']);
});

Route::get('/', [ExpenseController::class, 'index']);
Route::post('/', [ExpenseController::class, 'store']);
Route::get('/reports', [ExpenseController::class, 'reports']);
Route::get('/totals', [ExpenseController::class, 'getTotals']);
Route::get('/{expense}', [ExpenseController::class, 'show']);
Route::put('/{expense}', [ExpenseController::class, 'update']);
Route::delete('/{expense}', [ExpenseController::class, 'destroy']);

// Add expense-reports route for frontend compatibility
Route::get('/expense-reports', [ExpenseController::class, 'reports']);