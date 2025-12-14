<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmallMcProductController;

// Remove auth middleware for testing
Route::get('/small-mc-products', [SmallMcProductController::class, 'index']);
Route::post('/small-mc-products', [SmallMcProductController::class, 'store']);
Route::get('/small-mc-products/{id}', [SmallMcProductController::class, 'show']);
Route::put('/small-mc-products/{id}', [SmallMcProductController::class, 'update']);
Route::delete('/small-mc-products/{id}', [SmallMcProductController::class, 'destroy']);