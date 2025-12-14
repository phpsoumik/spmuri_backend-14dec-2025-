<?php
// Increase memory limit
ini_set('memory_limit', '1024M');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

// Load Laravel environment
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON data from request
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }
        
        // Validate required fields
        if (!isset($data['temp_file']) || !isset($data['selected_rows']) || 
            !isset($data['customerId']) || !isset($data['userId'])) {
            throw new Exception('Missing required fields');
        }
        
        $tempFile = $data['temp_file'];
        $selectedRows = $data['selected_rows'];
        $customerId = $data['customerId'];
        $userId = $data['userId'];
        
        // Check if temp file exists
        $filePath = __DIR__ . '/' . $tempFile;
        if (!file_exists($filePath)) {
            throw new Exception('Temporary file not found');
        }
        
        // Read temp file
        $fileContent = file_get_contents($filePath);
        $fileData = json_decode($fileContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in temp file: ' . json_last_error_msg());
        }
        
        if (!isset($fileData['rows']) || !is_array($fileData['rows'])) {
            throw new Exception('Invalid data format in temp file');
        }
        
        $rows = $fileData['rows'];
        $importedData = [];
        
        // Get Laravel models
        $productModel = new \App\Models\Product();
        $saleInvoiceModel = new \App\Models\SaleInvoice();
        $saleInvoiceProductModel = new \App\Models\SaleInvoiceProduct();
        
        // Start database transaction
        \Illuminate\Support\Facades\DB::beginTransaction();
        
        // Process selected rows
        foreach ($selectedRows as $index) {
            if (!isset($rows[$index])) {
                continue;
            }
            
            $row = $rows[$index];
            
            // Skip rows without MEMO NO or DATE
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }
            
            // Check if it's an "ONLY PAID" entry (no MEMO NO but has DATE and CREDIT)
            $isOnlyPaid = empty($row[0]) && !empty($row[1]) && !empty($row[8]);
            
            if ($isOnlyPaid) {
                // For "ONLY PAID" entries, we just need to create a transaction record
                // But we'll skip this for now as it's not part of the main import
                continue;
            }
            
            // Parse date
            $date = null;
            if (!empty($row[1])) {
                try {
                    $date = \Carbon\Carbon::createFromFormat('d-m-Y', $row[1])->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        $date = \Carbon\Carbon::parse($row[1])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $date = date('Y-m-d');
                    }
                }
            } else {
                $date = date('Y-m-d');
            }
            
            // Create or find Product
            $productName = !empty($row[2]) ? $row[2] : 'Unknown Product';
            $product = $productModel->firstOrCreate(
                ['name' => $productName],
                [
                    'productCode' => 'P' . rand(1000, 9999),
                    'productQuantity' => 0,
                    'productSalePrice' => !empty($row[6]) ? floatval($row[6]) : 0,
                    'productPurchasePrice' => 0,
                    'reorderQuantity' => 0,
                    'status' => 'true'
                ]
            );
            
            // Create SaleInvoice
            $memoNo = !empty($row[0]) ? $row[0] : null;
            $debitAmount = !empty($row[7]) ? floatval($row[7]) : 0;
            $creditAmount = !empty($row[8]) ? floatval($row[8]) : 0;
            $outstandingAmount = !empty($row[10]) ? floatval($row[10]) : 0;
            
            $saleInvoice = $saleInvoiceModel->firstOrCreate(
                ['id' => $memoNo],
                [
                    'date' => $date,
                    'totalAmount' => $debitAmount,
                    'totalTaxAmount' => 0,
                    'totalDiscountAmount' => !empty($row[9]) ? floatval($row[9]) : 0,
                    'paidAmount' => $creditAmount,
                    'profit' => 0,
                    'dueAmount' => $outstandingAmount,
                    'customerId' => $customerId,
                    'userId' => $userId,
                    'isHold' => 'false',
                    'orderStatus' => 'RECEIVED'
                ]
            );
            
            // Create SaleInvoiceProduct
            $bag = !empty($row[3]) ? floatval($row[3]) : 0;
            $kg = !empty($row[4]) ? floatval($row[4]) : 0;
            $quantity = !empty($row[5]) ? floatval($row[5]) : ($bag * $kg);
            $rate = !empty($row[6]) ? floatval($row[6]) : 0;
            
            $saleInvoiceProduct = $saleInvoiceProductModel->create([
                'invoiceId' => $saleInvoice->id,
                'productId' => $product->id,
                'productQuantity' => $quantity,
                'bag' => $bag,
                'kg' => $kg,
                'productUnitSalePrice' => $rate,
                'productDiscount' => 0,
                'productFinalAmount' => $quantity * $rate,
                'tax' => 0,
                'taxAmount' => 0
            ]);

            $importedData[] = [
                'memoNo' => $memoNo,
                'date' => $date,
                'product' => $productName,
                'saleInvoiceId' => $saleInvoice->id,
                'saleInvoiceProductId' => $saleInvoiceProduct->id
            ];
        }
        
        // Commit transaction
        \Illuminate\Support\Facades\DB::commit();
        
        // Delete temp file
        @unlink($filePath);
        
        echo json_encode([
            'message' => 'Data imported successfully',
            'imported_data' => $importedData,
            'total_imported' => count($importedData)
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        if (class_exists('\Illuminate\Support\Facades\DB')) {
            \Illuminate\Support\Facades\DB::rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Error importing data: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}