<?php

// Test route directly
echo "Testing route access...\n\n";

// Test simple-test route first
$url = 'http://localhost:8000/api/simple-test';
echo "Testing: $url\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Server not running or route not accessible\n";
    echo "Please start server: php artisan serve --port=8000\n";
} else {
    echo "✅ Server is running\n";
    echo "Response: $response\n\n";
    
    // Now test small-mc-products
    $url2 = 'http://localhost:8000/api/small-mc-products';
    echo "Testing: $url2\n";
    
    $response2 = @file_get_contents($url2, false, $context);
    
    if ($response2 === false) {
        echo "❌ Small MC Products route not working\n";
    } else {
        echo "✅ Small MC Products route working\n";
        echo "Response: $response2\n";
    }
}

echo "\nTest completed!\n";
?>