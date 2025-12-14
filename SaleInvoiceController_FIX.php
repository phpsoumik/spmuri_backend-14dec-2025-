<?php

namespace App\Http\Controllers;

use App\Models\SaleInvoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class SaleInvoiceControllerFix extends Controller
{
    // Fix for getAllSaleInvoice to show updated paid/due amounts
    public function fixSaleInvoiceAmounts(): JsonResponse
    {
        try {
            // Get all sale invoices
            $allSaleInvoices = SaleInvoice::all();
            
            foreach ($allSaleInvoices as $invoice) {
                // Calculate paid amount from transactions
                $totalPaid = Transaction::where('type', 'sale')
                    ->where('relatedId', $invoice->id)
                    ->where('creditId', 4)
                    ->sum('amount');
                
                // Calculate total amount from transactions
                $totalAmount = Transaction::where('type', 'sale')
                    ->where('relatedId', $invoice->id)
                    ->where('debitId', 4)
                    ->sum('amount');
                
                // Calculate return amount
                $totalReturnAmount = Transaction::where('type', 'sale_return')
                    ->where('relatedId', $invoice->id)
                    ->where('creditId', 4)
                    ->sum('amount');
                
                // Calculate instant return amount
                $instantReturnAmount = Transaction::where('type', 'sale_return')
                    ->where('relatedId', $invoice->id)
                    ->where('debitId', 4)
                    ->sum('amount');
                
                // Calculate due amount
                $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantReturnAmount;
                
                // Update the invoice
                $invoice->update([
                    'paidAmount' => takeUptoThreeDecimal($totalPaid),
                    'dueAmount' => takeUptoThreeDecimal($totalDueAmount)
                ]);
            }
            
            return response()->json(['message' => 'All sale invoices updated successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}