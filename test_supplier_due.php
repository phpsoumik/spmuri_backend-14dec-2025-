<?php
require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'spmuri_pos',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Test supplier current due
try {
    $suppliers = Capsule::table('supplier')->select('id', 'name', 'current_due_amount')->get();
    
    echo "Suppliers with current due:\n";
    foreach ($suppliers as $supplier) {
        echo "ID: {$supplier->id}, Name: {$supplier->name}, Current Due: {$supplier->current_due_amount}\n";
    }
    
    // Test purchase invoice with supplier
    $purchaseInvoices = Capsule::table('purchaseinvoice')
        ->join('supplier', 'purchaseinvoice.supplierId', '=', 'supplier.id')
        ->select('purchaseinvoice.id', 'purchaseinvoice.supplierId', 'supplier.name', 'supplier.current_due_amount')
        ->limit(5)
        ->get();
    
    echo "\nPurchase Invoices with Supplier Due:\n";
    foreach ($purchaseInvoices as $invoice) {
        echo "Invoice ID: {$invoice->id}, Supplier: {$invoice->name}, Current Due: {$invoice->current_due_amount}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>