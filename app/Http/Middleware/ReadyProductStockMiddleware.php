<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ReadyProductStockMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Add any authentication or permission checks here if needed
        return $next($request);
    }
}