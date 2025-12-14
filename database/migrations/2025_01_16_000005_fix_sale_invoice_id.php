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
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Clear all related data
        DB::table('saleInvoiceProduct')->truncate();
        DB::table('returnSaleInvoice')->truncate();
        DB::statement('DELETE FROM transaction WHERE type = "sale" OR type = "sale_return"');
        DB::table('saleInvoice')->truncate();
        
        // Drop all foreign key constraints first
        try {
            Schema::table('saleInvoiceProduct', function (Blueprint $table) {
                $table->dropForeign(['invoiceId']);
            });
        } catch (Exception $e) {
            // Ignore if constraint doesn't exist
        }
        
        try {
            Schema::table('returnSaleInvoice', function (Blueprint $table) {
                $table->dropForeign(['saleInvoiceId']);
            });
        } catch (Exception $e) {
            // Ignore if constraint doesn't exist
        }
        
        // Drop and recreate saleInvoice table
        Schema::dropIfExists('saleInvoice');
        
        Schema::create('saleInvoice', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('invoiceMemoNo')->nullable();
            $table->decimal('totalAmount', 15, 2)->default(0);
            $table->decimal('totalTaxAmount', 15, 2)->default(0);
            $table->decimal('totalDiscountAmount', 15, 2)->default(0);
            $table->decimal('paidAmount', 15, 2)->default(0);
            $table->decimal('dueAmount', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->unsignedBigInteger('customerId');
            $table->text('note')->nullable();
            $table->date('dueDate')->nullable();
            $table->text('termsAndConditions')->nullable();
            $table->unsignedBigInteger('userId');
            $table->string('isHold')->default('false');
            $table->string('orderStatus')->default('PENDING');
            $table->string('address')->nullable();
            $table->timestamps();
            
            $table->foreign('customerId')->references('id')->on('customer');
            $table->foreign('userId')->references('id')->on('users');
        });
        
        // Update saleInvoiceProduct table
        Schema::table('saleInvoiceProduct', function (Blueprint $table) {
            $table->unsignedBigInteger('invoiceId')->change();
            $table->foreign('invoiceId')->references('id')->on('saleInvoice')->onDelete('cascade');
        });
        
        // Update returnSaleInvoice table
        Schema::table('returnSaleInvoice', function (Blueprint $table) {
            $table->unsignedBigInteger('saleInvoiceId')->change();
            $table->foreign('saleInvoiceId')->references('id')->on('saleInvoice')->onDelete('cascade');
        });
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible
    }
};