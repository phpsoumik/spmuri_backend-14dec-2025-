<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchaseInvoiceProduct', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['productId']);
            
            // Add new foreign key constraint to purchase_products table
            $table->foreign('productId')->references('id')->on('purchase_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchaseInvoiceProduct', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['productId']);
            
            // Restore the original foreign key constraint to product table
            $table->foreign('productId')->references('id')->on('product');
        });
    }
};