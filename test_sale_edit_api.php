<?php

// Test the sale edit API endpoint
$baseUrl = 'http://localhost:8000';
$saleId = 80; // Replace with actual sale ID

// Sample data for testing
$testData = [
    'saleInvoiceProduct' => [
        [
            'id' => 123, // Replace with actual sale invoice product ID
            'bag' => 15,
            'kg' => 10,
            'productQuantity' => 150,
            'productUnitSalePrice' => 100,
            'productDiscount' => 0,
            'productFinalAmount' => 15000
        ]
    ],
    'stockAdjustments' => [
        [
            'productId' => null,
            'readyProductStockItemId' => 456, // Replace with actual ready product stock item ID
            'bagAdjustment' => 5, // 5 bags will be added back to stock
            'quantityAdjustment' => 50, // 50 kg will be added back to stock
            'newBag' => 15,
            'newQuantity' => 150,
            'hasChange' => true
        ]
    ]
];

// Convert to JSON
$jsonData = json_encode($testData);

// Initialize cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "$baseUrl/sale-invoice/$saleId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer YOUR_TOKEN_HERE' // Replace with actual token
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    echo "✅ Sale edit API is working!\n";
} else {
    echo "❌ Sale edit API failed!\n";
}
?>