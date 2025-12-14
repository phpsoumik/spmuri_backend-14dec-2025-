<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\AppSettingController;
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

Route::get('/', [AppSettingController::class, 'getSingleAppSetting']);

// CORS test route
Route::get('/test-cors', function() {
    return response()->json([
        'success' => true,
        'message' => 'CORS is working!',
        'timestamp' => now()
    ]);
});
Route::middleware(["permission:update-setting", 'fileUploader:1'])->put("/", [AppSettingController::class, 'updateAppSetting']);

// Company GST settings routes
Route::get('/company-settings', [\App\Http\Controllers\CompanySettingsController::class, 'getCompanySettings']);
Route::middleware(["permission:update-setting"])->put('/company-settings', [\App\Http\Controllers\CompanySettingsController::class, 'updateCompanySettings']);
