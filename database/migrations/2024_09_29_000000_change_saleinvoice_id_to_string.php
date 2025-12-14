<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Step 1: Drop foreign keys safely
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Step 2: Change saleinvoice id to varchar
            DB::statement('ALTER TABLE saleinvoice MODIFY id VARCHAR(50) NOT NULL');
            
            // Step 3: Change related tables
            DB::statement('ALTER TABLE saleInvoiceProduct MODIFY invoiceId VARCHAR(50) NOT NULL');
            DB::statement('ALTER TABLE transaction MODIFY relatedId VARCHAR(50)');
            
            // Step 4: Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
        } catch (Exception $e) {
            // If error, re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible
    }
};