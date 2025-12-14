<?php

require_once 'vendor/autoload.php';

use App\Models\SmallMcProduct;

// Test direct database insert with date
try {
    $product = SmallMcProduct::create([
        'item' => 'TEST001',
        'item_name' => 'Test Product',
        'amount' => 100.50,
        'date' => '2025-01-23'
    ]);
    
    echo "Product created successfully with ID: " . $product->id . "\n";
    echo "Date saved: " . $product->date . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}