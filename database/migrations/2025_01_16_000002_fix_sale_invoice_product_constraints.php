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
            // Drop the foreign key constraint first
            $table->dropForeign(['productId']);
            
            // Make productId nullable
            $table->unsignedBigInteger('productId')->nullable()->change();
            
            // Re-add the foreign key constraint but allow null values
            $table->foreign('productId')->references('id')->on('product')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saleInvoiceProduct', function (Blueprint $table) {
            $table->dropForeign(['productId']);
            $table->unsignedBigInteger('productId')->nullable(false)->change();
            $table->foreign('productId')->references('id')->on('product');
        });
    }
};