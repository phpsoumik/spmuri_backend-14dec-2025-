<?php

// Simple test without curl
echo "Testing Small M/C Product API...\n\n";

// Test if the route is accessible
$url = 'http://localhost:8000/api/small-mc-products';

echo "Testing GET $url\n";

// Use file_get_contents for simple GET request
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Error: Could not connect to API\n";
    echo "Make sure Laravel server is running on port 8000\n";
} else {
    echo "Response: $response\n";
}

echo "\nTest completed!\n";
?>