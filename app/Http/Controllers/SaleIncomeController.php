<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleIncomeController extends Controller
{
    public function getTotalPaidAmount()
    {
        try {
            $totalPaid = DB::table('sale_invoices')
                ->sum('paid_amount');
                
            return response()->json([
                'success' => true,
                'message' => 'success',
                'total_paid_amount' => $totalPaid ?? 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}