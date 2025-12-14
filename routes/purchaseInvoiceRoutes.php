<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseInvoiceController;

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

Route::middleware('permission:create-purchaseInvoice')->post("/", [PurchaseInvoiceController::class, 'createSinglePurchaseInvoice']);

// Development bypass - remove in production
Route::get("/dev", [PurchaseInvoiceController::class, 'getAllPurchaseInvoice']);

Route::middleware('permission:readAll-purchaseInvoice')->get("/", [PurchaseInvoiceController::class, 'getAllPurchaseInvoice']);

Route::middleware('permission:readAll-purchaseInvoice')->get("/{id}", [PurchaseInvoiceController::class, 'getSinglePurchaseInvoice']);

Route::middleware('permission:update-purchaseInvoice')->put("/{id}", [PurchaseInvoiceController::class, 'updatePurchaseInvoice']);

Route::middleware('permission:delete-purchaseInvoice')->delete("/{id}", [PurchaseInvoiceController::class, 'deletePurchaseInvoice']);

// Get purchase invoice products for raw materials (no auth required)
Route::get("/products", [PurchaseInvoiceController::class, 'getPurchaseInvoiceProducts']);
