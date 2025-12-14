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
        Schema::table('saleInvoiceProduct', function (Blueprint $table) {
            // Make productId nullable to allow ready product stock items
            $table->unsignedBigInteger('productId')->nullable()->change();
            
            // Add field for ready product stock item reference
            $table->unsignedBigInteger('ready_product_stock_item_id')->nullable()->after('productId');
            
            // Add foreign key for ready product stock items
            $table->foreign('ready_product_stock_item_id')->references('id')->on('ready_product_stock_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saleInvoiceProduct', function (Blueprint $table) {
            $table->dropForeign(['ready_product_stock_item_id']);
            $table->dropColumn('ready_product_stock_item_id');
            $table->unsignedBigInteger('productId')->nullable(false)->change();
        });
    }
};