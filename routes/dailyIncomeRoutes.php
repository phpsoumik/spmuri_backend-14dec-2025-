<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DailyIncomeController;

Route::get('/', [DailyIncomeController::class, 'index']);
Route::post('/', [DailyIncomeController::class, 'store']);
Route::get('/total', [DailyIncomeController::class, 'total']);
Route::get('/balance', [DailyIncomeController::class, 'balance']);
Route::post('/update', [DailyIncomeController::class, 'update']);
Route::delete('/{dailyIncome}', [DailyIncomeController::class, 'destroy']);
Route::get('/{dailyIncome}', [DailyIncomeController::class, 'show']);