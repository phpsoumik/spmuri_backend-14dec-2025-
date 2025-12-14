<?php

// Test Small M/C Product API endpoints
require_once 'vendor/autoload.php';

echo "Testing Small M/C Product API...\n\n";

// Test data
$testProduct = [
    'item' => '9127',
    'item_name' => 'Test Product',
    'amount' => 598.00
];

// Base URL
$baseUrl = 'http://localhost:8000/api';

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => $response];
}

// Test 1: Create a product
echo "1. Testing POST /small-mc-products\n";
$response = makeRequest("$baseUrl/small-mc-products", 'POST', $testProduct);
echo "Status: " . $response['code'] . "\n";
echo "Response: " . $response['body'] . "\n\n";

// Test 2: Get all products
echo "2. Testing GET /small-mc-products\n";
$response = makeRequest("$baseUrl/small-mc-products");
echo "Status: " . $response['code'] . "\n";
echo "Response: " . $response['body'] . "\n\n";

echo "API test completed!\n";
?>