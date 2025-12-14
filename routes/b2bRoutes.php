<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\B2BCompanyController;
use App\Http\Controllers\B2BSaleController;

/*
|--------------------------------------------------------------------------
| B2B API Routes
|--------------------------------------------------------------------------
*/

// B2B Company routes
Route::prefix('companies')->group(function () {
    Route::get('/', [B2BCompanyController::class, 'index']);
    Route::post('/', [B2BCompanyController::class, 'store']);
    Route::get('/{id}', [B2BCompanyController::class, 'show']);
    Route::put('/{id}', [B2BCompanyController::class, 'update']);
    Route::delete('/{id}', [B2BCompanyController::class, 'destroy']);
    Route::post('/{parentId}/sub-companies', [B2BCompanyController::class, 'addSubCompany']);
});

// B2B Sales routes
Route::prefix('sales')->group(function () {
    Route::get('/', [B2BSaleController::class, 'index']);
    Route::post('/', [B2BSaleController::class, 'store']);
    Route::get('/{id}', [B2BSaleController::class, 'show']);
    Route::put('/{id}', [B2BSaleController::class, 'update']);
    Route::delete('/{id}', [B2BSaleController::class, 'destroy']);
    Route::get('/{id}/invoice', [B2BSaleController::class, 'generateInvoice']);
    Route::post('/{id}/payment', [B2BSaleController::class, 'addPayment']);
});