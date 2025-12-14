<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleInvoiceControllerFix;

Route::prefix('api')->group(function () {
    Route::get('/fix-sale-amounts', [SaleInvoiceControllerFix::class, 'fixSaleInvoiceAmounts']);
});