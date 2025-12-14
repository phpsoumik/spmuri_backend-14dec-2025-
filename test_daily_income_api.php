<?php

// Test the Daily Income API endpoints

$baseUrl = 'http://localhost:8000/api/daily-income';

echo "Testing Daily Income API...\n\n";

// Test 1: Get all daily incomes
echo "1. Testing GET /api/daily-income\n";
$response = file_get_contents($baseUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "Failed to get response\n\n";
}

// Test 2: Get total
echo "2. Testing GET /api/daily-income/total\n";
$response = file_get_contents($baseUrl . '/total');
if ($response) {
    $data = json_decode($response, true);
    echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "Failed to get response\n\n";
}

echo "API test completed!\n";