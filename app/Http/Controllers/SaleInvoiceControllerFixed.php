<?php

namespace App\Http\Controllers;

use DateTime;
use Exception;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ReturnSaleInvoice;
use Illuminate\Http\JsonResponse;
use App\Models\SaleInvoiceProduct;
use App\Models\ReadyProductStockItem;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\error;

class SaleInvoiceControllerFixed extends Controller
{
    // Fix for paid amount and due amount calculation
    public function fixPaidDueCalculation(): JsonResponse
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