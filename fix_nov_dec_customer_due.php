<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Get all November-December 2025 invoices
    $invoices = DB::table('saleinvoice')
        ->whereMonth('date', '>=', 11)
        ->whereYear('date', 2025)
        ->orderBy('date', 'asc')
        ->orderBy('id', 'asc')
        ->get();

    echo "Found " . count($invoices) . " invoices to fix\n\n";

    foreach ($invoices as $invoice) {
        // Get customer
        $customer = DB::table('customer')->where('id', $invoice->customerId)->first();
        
        if (!$customer) {
            echo "Invoice {$invoice->id}: Customer not found\n";
            continue;
        }

        // Get all previous invoices for this customer (before this invoice)
        $previousInvoices = DB::table('saleinvoice')
            ->where('customerId', $invoice->customerId)
            ->where(function($query) use ($invoice) {
                $query->where('date', '<', $invoice->date)
                      ->orWhere(function($q) use ($invoice) {
                          $q->where('date', '=', $invoice->date)
                            ->where('id', '<', $invoice->id);
                      });
            })
            ->get();

        // Calculate customer previous due (before this invoice)
        $customerPreviousDue = 0;
        foreach ($previousInvoices as $prevInv) {
            $prevInvoiceDue = ($prevInv->totalAmount ?? 0) + ($prevInv->commission_value ?? 0) + 
                            (($prevInv->bag_quantity ?? 0) * ($prevInv->bag_price ?? 0)) - 
                            ($prevInv->paidAmount ?? 0);
            $customerPreviousDue += $prevInvoiceDue;
        }

        // Calculate this invoice due
        $invoiceDue = ($invoice->totalAmount ?? 0) + ($invoice->commission_value ?? 0) + 
                     (($invoice->bag_quantity ?? 0) * ($invoice->bag_price ?? 0)) - 
                     ($invoice->paidAmount ?? 0);

        // Customer current due = previous due + this invoice due
        $customerCurrentDue = $customerPreviousDue + $invoiceDue;

        // Update invoice
        DB::table('saleinvoice')
            ->where('id', $invoice->id)
            ->update([
                'customer_previous_due' => round($customerPreviousDue, 3),
                'customer_current_due' => round($customerCurrentDue, 3)
            ]);

        echo "Invoice {$invoice->id} (Customer: {$customer->username}): ";
        echo "Previous Due: Rs " . number_format($customerPreviousDue, 2);
        echo ", Invoice Due: Rs " . number_format($invoiceDue, 2);
        echo ", Current Due: Rs " . number_format($customerCurrentDue, 2) . "\n";
    }

    echo "\n✓ All invoices updated successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
