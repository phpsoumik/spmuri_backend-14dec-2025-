<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleReturnAdjustmentController;

/*
|--------------------------------------------------------------------------
| Sale Return Adjustment Routes
|--------------------------------------------------------------------------
*/

Route::post("/", [SaleReturnAdjustmentController::class, 'createAdjustment']);

Route::get("/", [SaleReturnAdjustmentController::class, 'getAllAdjustments']);

Route::get("/{id}", [SaleReturnAdjustmentController::class, 'getSingleAdjustment']);

Route::patch("/{id}", [SaleReturnAdjustmentController::class, 'deleteAdjustment']);