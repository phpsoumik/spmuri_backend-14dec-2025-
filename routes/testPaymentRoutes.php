<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\PurchaseInvoice;

/*
|--------------------------------------------------------------------------
| Test Payment Routes
|--------------------------------------------------------------------------
*/

Route::get('/test-payment-update', function (Request $request) {
    try {
        // Get a purchase invoice for testing
        $invoice = PurchaseInvoice::first();
        
        if (!$invoice) {
            return response()->json(['error' => 'No purchase invoices found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Payment update test endpoint working',
            'invoice' => [
                'id' => $invoice->id,
                'totalAmount' => $invoice->totalAmount,
                'paidAmount' => $invoice->paidAmount,
                'dueAmount' => $invoice->dueAmount,
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::post('/test-payment-add', function (Request $request) {
    try {
        $invoiceId = $request->input('invoiceId');
        $paymentAmount = $request->input('amount', 100);
        
        $invoice = PurchaseInvoice::find($invoiceId);
        
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }
        
        // Update payment amounts
        $newPaidAmount = $invoice->paidAmount + $paymentAmount;
        $newDueAmount = $invoice->dueAmount - $paymentAmount;
        
        $invoice->update([
            'paidAmount' => $newPaidAmount,
            'dueAmount' => $newDueAmount
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'before' => [
                'paidAmount' => $invoice->paidAmount - $paymentAmount,
                'dueAmount' => $invoice->dueAmount + $paymentAmount,
            ],
            'after' => [
                'paidAmount' => $invoice->paidAmount,
                'dueAmount' => $invoice->dueAmount,
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});