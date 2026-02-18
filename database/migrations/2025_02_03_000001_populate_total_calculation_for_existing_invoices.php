<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing sale invoices with total_calculation from transaction data
        $invoices = DB::table('saleinvoice')
            ->where('total_calculation', 0)
            ->orWhereNull('total_calculation')
            ->get();

        foreach ($invoices as $invoice) {
            // Get total amount from transaction
            $totalAmount = DB::table('transaction')
                ->where('type', 'sale')
                ->where('relatedId', $invoice->id)
                ->where('debitId', 4)
                ->sum('amount');

            // If no transaction found, use totalAmount field
            if ($totalAmount == 0 && isset($invoice->totalAmount)) {
                $totalAmount = $invoice->totalAmount;
            }

            // Update the invoice
            if ($totalAmount > 0) {
                DB::table('saleinvoice')
                    ->where('id', $invoice->id)
                    ->update(['total_calculation' => $totalAmount]);
            }
        }
    }

    public function down(): void
    {
        // Reset total_calculation to 0 for rollback
        DB::table('saleinvoice')->update(['total_calculation' => 0]);
    }
};
