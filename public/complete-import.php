<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Increase memory limit and execution time for large files
ini_set('memory_limit', '2048M'); // 2GB memory limit
ini_set('max_execution_time', 600); // 10 minutes
set_time_limit(600); // Also set time limit to 10 minutes

// Load Laravel environment
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceProduct;
use Illuminate\Support\Facades\DB;

// Check if kg field exists in saleInvoiceProduct table
function checkKgField() {
    try {
        $hasKgField = DB::select("SHOW COLUMNS FROM saleInvoiceProduct LIKE 'kg'");
        return !empty($hasKgField);
    } catch (Exception $e) {
        return false;
    }
}

// Add kg field if it doesn't exist
function addKgField() {
    try {
        if (!checkKgField()) {
            DB::statement("ALTER TABLE saleInvoiceProduct ADD COLUMN kg DOUBLE DEFAULT 0 AFTER bag");
            return true;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Check if sku field exists in product table
function checkSkuField() {
    try {
        $hasSkuField = DB::select("SHOW COLUMNS FROM product LIKE 'sku'");
        return !empty($hasSkuField);
    } catch (Exception $e) {
        return false;
    }
}

// Check if sku field has a default value
function checkSkuDefault() {
    try {
        $skuField = DB::select("SHOW COLUMNS FROM product LIKE 'sku'");
        if (!empty($skuField) && $skuField[0]->Default === null && $skuField[0]->Null === 'NO') {
            return false; // No default value and NOT NULL
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Make sku field nullable
function makeSkuNullable() {
    try {
        DB::statement("ALTER TABLE product MODIFY sku VARCHAR(255) NULL");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Show product table structure
function showProductStructure() {
    $structure = DB::select("SHOW CREATE TABLE product")[0];
    $columns = DB::select("DESCRIBE product");
    $indexes = DB::select("SHOW INDEX FROM product");
    
    return [
        'create_table' => $structure->{'Create Table'},
        'columns' => $columns,
        'indexes' => $indexes
    ];
}

// Process the uploaded CSV file
function processCSV($file, $skipRows, $maxRows, $customerId, $userId) {
    $results = [
        'success' => false,
        'message' => '',
        'imported' => 0,
        'errors' => []
    ];
    
    try {
        // Check if kg field exists, if not add it
        addKgField();
        
        // Read CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            $results['message'] = 'Could not open file';
            return $results;
        }
        
        // Skip header rows
        for ($i = 0; $i < $skipRows; $i++) {
            fgetcsv($handle);
        }
        
        $rowCount = 0;
        $importedCount = 0;
        $batchSize = 500; // Process in larger batches of 500 rows
        $batchCount = 0;
        
        // Count total rows for progress calculation
        $totalRows = 0;
        if ($maxRows <= 0) {
            // Don't count rows for large files to avoid performance issues
            $totalRows = 1000000; // Just use a large number for progress calculation
        } else {
            $totalRows = $maxRows;
        }
        
        // Process rows
        while (($row = fgetcsv($handle)) !== false && ($maxRows <= 0 || $rowCount < $maxRows)) {
            // Start a new transaction for each batch
            if ($rowCount % $batchSize === 0) {
                if ($batchCount > 0) {
                    // Commit previous batch
                    DB::commit();
                }
                // Start new batch
                DB::beginTransaction();
                $batchCount++;
            }
            
            $rowCount++;
            
            // Skip empty rows
            if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                continue;
            }
            
            // Check if it's an "ONLY PAID" entry (no MEMO NO but has DATE and CREDIT)
            $isOnlyPaid = empty($row[0]) && !empty($row[1]) && !empty($row[8]);
            
            if ($isOnlyPaid) {
                // For "ONLY PAID" entries, we just need to create a transaction record
                // But we'll skip this for now as it's not part of the main import
                continue;
            }
            
            try {
                // Parse date
                $date = null;
                if (!empty($row[1])) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('d-m-Y', $row[1])->format('Y-m-d');
                    } catch (Exception $e) {
                        try {
                            $date = \Carbon\Carbon::parse($row[1])->format('Y-m-d');
                        } catch (Exception $e) {
                            $date = date('Y-m-d');
                        }
                    }
                } else {
                    $date = date('Y-m-d');
                }
                
                // Create or find Product
                $productName = !empty($row[2]) ? $row[2] : 'Unknown Product';
                
                // Check if product exists
                $product = Product::where('name', $productName)->first();
                
                if (!$product) {
                    try {
                        // Try to create product without SKU first
                        $productData = [
                            'name' => $productName,
                            'productQuantity' => 0,
                            'productSalePrice' => !empty($row[6]) ? floatval($row[6]) : 0,
                            'productPurchasePrice' => 0,
                            'reorderQuantity' => 0,
                            'status' => 'true'
                        ];
                        
                        // Check if sku field is required
                        $hasSkuField = checkSkuField();
                        $hasSkuDefault = checkSkuDefault();
                        
                        if ($hasSkuField && !$hasSkuDefault) {
                            // If SKU is required, add a unique SKU
                            $productData['sku'] = 'SKU-' . rand(10000, 99999) . '-' . time();
                        }
                        
                        $product = Product::create($productData);
                    } catch (\Exception $e) {
                        // If creation fails, try with a unique SKU
                        $productData['sku'] = 'SKU-' . rand(10000, 99999) . '-' . time() . '-' . rand(100, 999);
                        $product = Product::create($productData);
                    }
                }
                
                // Create SaleInvoice
                $memoNo = !empty($row[0]) ? $row[0] : null;
                $debitAmount = !empty($row[7]) ? floatval($row[7]) : 0;
                $creditAmount = !empty($row[8]) ? floatval($row[8]) : 0;
                $outstandingAmount = !empty($row[10]) ? floatval($row[10]) : 0;
                
                $saleInvoice = SaleInvoice::firstOrCreate(
                    ['id' => $memoNo],
                    [
                        'date' => $date,
                        'totalAmount' => $debitAmount,
                        'totalTaxAmount' => 0,
                        'totalDiscountAmount' => 0, // Skip DISCOUNT/REFUND
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
                
                $saleInvoiceProduct = SaleInvoiceProduct::create([
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
                
                $importedCount++;
                
                // Show progress every 10 rows
                if ($importedCount % 10 === 0) {
                    // If maxRows is 0, just show the count without percentage
                    if ($maxRows <= 0) {
                        echo "<script>document.getElementById('progress').style.width = '100%'; document.getElementById('progress-text').innerText = '{$importedCount} rows imported'; </script>";
                    } else {
                        $progress = $totalRows > 0 ? round(($rowCount / $totalRows) * 100) : 0;
                        echo "<script>document.getElementById('progress').style.width = '{$progress}%'; document.getElementById('progress-text').innerText = '{$importedCount} rows imported ({$progress}%)'; </script>";
                    }
                    echo "<script>document.getElementById('progress-container').style.display = 'block';</script>";
                    flush();
                    ob_flush();
                }
            } catch (Exception $e) {
                $results['errors'][] = "Error in row $rowCount: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        
        // Commit final batch if there are no errors
        if (empty($results['errors'])) {
            if (DB::transactionLevel() > 0) {
                DB::commit();
            }
            $results['success'] = true;
            $results['message'] = "Successfully imported $importedCount rows";
            $results['imported'] = $importedCount;
        } else {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $results['message'] = "Import failed with errors";
        }
    } catch (Exception $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        $results['message'] = "Error: " . $e->getMessage();
        $results['errors'][] = $e->getMessage();
    }
    
    return $results;
}

// Handle form submission
$message = '';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_kg_field'])) {
        // Check if kg field exists
        $hasKgField = checkKgField();
        $message = $hasKgField ? 'kg field exists in saleInvoiceProduct table' : 'kg field does not exist in saleInvoiceProduct table';
    } else if (isset($_POST['add_kg_field'])) {
        // Add kg field
        $added = addKgField();
        $message = $added ? 'kg field added successfully' : 'kg field already exists or could not be added';
    } else if (isset($_POST['check_sku_field'])) {
        // Check if sku field exists and has default value
        $hasSkuField = checkSkuField();
        $hasSkuDefault = checkSkuDefault();
        if (!$hasSkuField) {
            $message = 'sku field does not exist in product table';
        } else if (!$hasSkuDefault) {
            $message = 'sku field exists but does not have a default value and is NOT NULL';
        } else {
            $message = 'sku field exists and has a default value or is nullable';
        }
        
        // Show product table structure
        $productStructure = showProductStructure();
    } else if (isset($_POST['make_sku_nullable'])) {
        // Make sku field nullable
        $made_nullable = makeSkuNullable();
        $message = $made_nullable ? 'sku field is now nullable' : 'Could not make sku field nullable';
        
        // Show product table structure
        $productStructure = showProductStructure();
    } else if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        // Process CSV file
        $skipRows = isset($_POST['skip_rows']) ? intval($_POST['skip_rows']) : 3;
        $maxRows = isset($_POST['max_rows']) ? intval($_POST['max_rows']) : 0; // 0 means unlimited
        $customerId = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($customerId <= 0 || $userId <= 0) {
            $errors[] = 'Customer ID and User ID must be valid numbers';
        } else {
            $results = processCSV($_FILES['csv_file'], $skipRows, $maxRows, $customerId, $userId);
            $success = $results['success'];
            $message = $results['message'];
            if (!empty($results['errors'])) {
                $errors = $results['errors'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Import Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            padding: 20px;
        }
        .container {
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">CSV Import Tool</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php elseif (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($productStructure)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Product Table Structure</h5>
                </div>
                <div class="card-body">
                    <h6>Create Table SQL</h6>
                    <pre><?php echo $productStructure['create_table']; ?></pre>
                    
                    <h6>Columns</h6>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productStructure['columns'] as $column): ?>
                                <tr>
                                    <td><?php echo $column->Field; ?></td>
                                    <td><?php echo $column->Type; ?></td>
                                    <td><?php echo $column->Null; ?></td>
                                    <td><?php echo $column->Key; ?></td>
                                    <td><?php echo $column->Default ?? 'NULL'; ?></td>
                                    <td><?php echo $column->Extra; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Check/Add kg Field</h5>
            </div>
            <div class="card-body">
                <form method="post" class="mb-3">
                    <button type="submit" name="check_kg_field" class="btn btn-info">Check kg Field</button>
                    <button type="submit" name="add_kg_field" class="btn btn-warning">Add kg Field</button>
                    <button type="submit" name="check_sku_field" class="btn btn-info">Check SKU Field</button>
                    <button type="submit" name="make_sku_nullable" class="btn btn-warning">Make SKU Nullable</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Import CSV File</h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div id="progress-container" class="progress mb-3" style="display: none;">
                        <div id="progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="progress-text" class="mb-3" style="text-align: center;"></div>
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        <div class="form-text">CSV file should have columns: MEMO NO, DATE, PARTICULARS, BAG, KG, QUANTITY, RATE, DEBET AMOUNT, CREDIT, DISCOUNT/REFUND, OUTSTANDING</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="skip_rows" class="form-label">Skip Rows</label>
                            <input type="number" class="form-control" id="skip_rows" name="skip_rows" value="3" min="0" required>
                            <div class="form-text">Number of header rows to skip</div>
                        </div>
                        <div class="col-md-6">
                            <label for="max_rows" class="form-label">Max Rows</label>
                            <input type="number" class="form-control" id="max_rows" name="max_rows" value="0" min="0" required>
                            <small class="text-danger">সমস্ত ডাটা ইম্পোর্ট করতে এখানে 0 রাখুন!</small>
                            <div class="form-text"><strong>Maximum number of rows to import (use 0 for unlimited rows)</strong></div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer_id" class="form-label">Customer ID</label>
                            <input type="number" class="form-control" id="customer_id" name="customer_id" required>
                        </div>
                        <div class="col-md-6">
                            <label for="user_id" class="form-label">User ID</label>
                            <input type="number" class="form-control" id="user_id" name="user_id" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>