<?php
// Test script to verify payment update functionality
require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Database configuration
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'erp_laravel',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    // Test: Get a purchase invoice
    $invoice = Capsule::table('purchaseInvoice')->first();
    
    if ($invoice) {
        echo "Found Purchase Invoice ID: " . $invoice->id . "\n";
        echo "Current Paid Amount: " . $invoice->paidAmount . "\n";
        echo "Current Due Amount: " . $invoice->dueAmount . "\n";
        
        // Test payment update
        $paymentAmount = 100.00;
        $newPaidAmount = $invoice->paidAmount + $paymentAmount;
        $newDueAmount = $invoice->dueAmount - $paymentAmount;
        
        // Update the invoice
        Capsule::table('purchaseInvoice')
            ->where('id', $invoice->id)
            ->update([
                'paidAmount' => $newPaidAmount,
                'dueAmount' => $newDueAmount
            ]);
            
        echo "Test Payment of $paymentAmount added successfully!\n";
        echo "New Paid Amount: " . $newPaidAmount . "\n";
        echo "New Due Amount: " . $newDueAmount . "\n";
        
        // Verify the update
        $updatedInvoice = Capsule::table('purchaseInvoice')->where('id', $invoice->id)->first();
        echo "Verified - Paid Amount: " . $updatedInvoice->paidAmount . "\n";
        echo "Verified - Due Amount: " . $updatedInvoice->dueAmount . "\n";
        
    } else {
        echo "No purchase invoices found in database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>