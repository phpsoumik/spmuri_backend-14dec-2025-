<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleInvoiceControllerFixed;

Route::prefix('api')->group(function () {
    Route::get('/fix-paid-due', [SaleInvoiceControllerFixed::class, 'fixPaidDueCalculation']);
});