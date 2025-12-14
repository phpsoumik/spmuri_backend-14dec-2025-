<?php

use App\Http\Controllers\ReadyProductStockController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReadyProductStockController::class, 'index']);
Route::post('/', [ReadyProductStockController::class, 'store']);
Route::get('/{id}', [ReadyProductStockController::class, 'show']);
Route::put('/{id}', [ReadyProductStockController::class, 'update']);
Route::put('/{id}/renew', [ReadyProductStockController::class, 'renewStock']);
Route::delete('/{id}', [ReadyProductStockController::class, 'destroy']);