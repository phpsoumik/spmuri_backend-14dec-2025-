<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartOrderController;

// Cart Order Routes
Route::get('cart-order', [CartOrderController::class, 'index']);
Route::get('cart-order/{id}', [CartOrderController::class, 'show']);
Route::post('cart-order', [CartOrderController::class, 'store']);
Route::patch('cart-order/order', [CartOrderController::class, 'update']);
Route::post('cart-order/reOrder', [CartOrderController::class, 'reOrder']);
