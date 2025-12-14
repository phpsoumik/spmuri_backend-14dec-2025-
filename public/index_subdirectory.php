<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// For subdirectory deployment - adjust path as needed
$maintenancePath = __DIR__.'/../../SPMURI_BACKEND/storage/framework/maintenance.php';
if (file_exists($maintenancePath)) {
    require $maintenancePath;
}

// Register the Composer autoloader...
require __DIR__.'/../../SPMURI_BACKEND/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../../SPMURI_BACKEND/bootstrap/app.php')
    ->make(Kernel::class)
    ->handle($request = Request::capture())
    ->send();