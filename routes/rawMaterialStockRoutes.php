<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RawMaterialStockController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get("/", [RawMaterialStockController::class, 'index']);

Route::get("/{id}", [RawMaterialStockController::class, 'show']);

Route::put("/{id}/update-stock", [RawMaterialStockController::class, 'updateStock']);

Route::delete("/{id}", [RawMaterialStockController::class, 'destroy']);

Route::get("/low-stock/alert", [RawMaterialStockController::class, 'lowStockAlert']);