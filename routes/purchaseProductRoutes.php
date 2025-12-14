<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseProductController;

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

Route::post("/", [PurchaseProductController::class, 'store']);

Route::get("/", [PurchaseProductController::class, 'index']);

Route::get("/{id}", [PurchaseProductController::class, 'show']);

Route::put("/{id}", [PurchaseProductController::class, 'update']);

Route::patch("/{id}", [PurchaseProductController::class, 'update']);

Route::delete("/{id}", [PurchaseProductController::class, 'destroy']);