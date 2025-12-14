<?php

use Illuminate\Support\Facades\Route;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;

// Test route without authentication
Route::get('/test-purchase', function () {
    try {
        $purchases = PurchaseInvoice::with('supplier:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        return response()->json([
            'success' => true,
            'count' => $purchases->count(),
            'data' => $purchases,
            'message' => 'Purchase data fetched successfully without authentication'
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Test payment update functionality
Route::get('/test-payment-update', function (Request $request) {
    try {
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
        
        $oldPaidAmount = $invoice->paidAmount;
        $oldDueAmount = $invoice->dueAmount;
        
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
                'paidAmount' => $oldPaidAmount,
                'dueAmount' => $oldDueAmount,
            ],
            'after' => [
                'paidAmount' => $newPaidAmount,
                'dueAmount' => $newDueAmount,
            ]
        ]);
        
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});