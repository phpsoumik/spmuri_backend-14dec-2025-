<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip this migration - database structure already correct from live import
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the changes
        Schema::table('purchaseInvoiceProduct', function (Blueprint $table) {
            $table->dropForeign(['invoiceId']);
            $table->dropColumn('invoiceId');
        });
        
        Schema::drop('purchaseInvoice');
        
        Schema::create('purchaseInvoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dateTime('date');
            $table->double('totalAmount');
            $table->double('totalTax')->nullable();
            $table->double('paidAmount');
            $table->double('dueAmount');
            $table->unsignedBigInteger('supplierId');
            $table->string('note')->nullable();
            $table->string('supplierMemoNo')->nullable();
            $table->string('invoiceMemoNo')->nullable();
            $table->timestamps();

            $table->foreign('supplierId')->references('id')->on('supplier');
        });
        
        Schema::table('purchaseInvoiceProduct', function (Blueprint $table) {
            $table->uuid('invoiceId')->after('id');
            $table->foreign('invoiceId')->references('id')->on('purchaseInvoice');
        });
    }
};