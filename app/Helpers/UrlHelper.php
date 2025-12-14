<?php

if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        // Check if running in console (artisan commands)
        if (app()->runningInConsole()) {
            return config('app.url');
        }
        
        // Get from request
        $request = request();
        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();
        
        // Build URL
        $url = $scheme . '://' . $host;
        
        // Add port if not standard
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }
        
        return $url;
    }
}

if (!function_exists('getApiUrl')) {
    function getApiUrl($endpoint = '') {
        $baseUrl = getBaseUrl();
        return $baseUrl . ($endpoint ? '/' . ltrim($endpoint, '/') : '');
    }
}