<?php
// This is a simple test script to check if the CSV import is working

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Laravel environment
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a test CSV file
$csvData = <<<CSV
AMBIKA MANNA
MEMO NO,DATE,PARTICULARS,BAG,KG,QUANTITY,RATE,DEBET AMOUNT,CREDIT,DISCOUNT/REFUND,OUTSTANDING,customerId,userId
1861,02-04-2024,SARU MURI,25,13,325,44.5,14462.5,0,,49865.5,238,1
1908,05-04-2024,SARU MURI,30,13,390,44.5,17355,,67220.5,238,1
CSV;

$tempCsvFile = __DIR__ . '/test.csv';
file_put_contents($tempCsvFile, $csvData);

echo "<h1>CSV Import Test</h1>";
echo "<h2>Step 1: Testing CSV Parsing</h2>";

// Test CSV parsing
$csvRows = array_map('str_getcsv', explode("\n", $csvData));
echo "<pre>CSV Rows: " . print_r($csvRows, true) . "</pre>";

// Skip the first 3 rows
$csvRows = array_slice($csvRows, 3);
echo "<pre>After skipping 3 rows: " . print_r($csvRows, true) . "</pre>";

// Filter out empty rows
$csvRows = array_filter($csvRows, function($row) {
    return is_array($row) && (count($row) > 1) && (!empty($row[0]) || !empty($row[1]) || !empty($row[2]));
});
echo "<pre>After filtering empty rows: " . print_r($csvRows, true) . "</pre>";

// Ensure all rows have at least 12 columns
$csvRows = array_map(function($row) {
    $padded = array_pad($row, 12, "");
    return array_slice($padded, 0, 12); // Limit to 12 columns
}, $csvRows);
echo "<pre>After padding rows: " . print_r($csvRows, true) . "</pre>";

// Test JSON encoding
$jsonData = json_encode(['rows' => array_values($csvRows)]);
echo "<h2>Step 2: Testing JSON Encoding</h2>";
echo "<pre>JSON Data: " . htmlspecialchars($jsonData) . "</pre>";

// Test JSON decoding
$decodedData = json_decode($jsonData, true);
echo "<h2>Step 3: Testing JSON Decoding</h2>";
echo "<pre>Decoded Data: " . print_r($decodedData, true) . "</pre>";

// Clean up
@unlink($tempCsvFile);

echo "<h2>Test Complete</h2>";
echo "<p>If you can see all the data above without errors, the CSV parsing and JSON handling is working correctly.</p>";
echo "<p>Now try using the <a href='/import-csv.html'>CSV Import Tool</a>.</p>";