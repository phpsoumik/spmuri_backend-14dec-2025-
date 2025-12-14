<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sale_return_adjustments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('return_sale_invoice_id');
            $table->enum('adjustment_type', ['cash_refund', 'product_exchange']);
            $table->decimal('cash_refund_amount', 10, 2)->default(0);
            $table->string('exchange_product_id')->nullable();
            $table->integer('exchange_quantity')->default(0);
            $table->decimal('exchange_bag', 10, 2)->default(0);
            $table->decimal('exchange_kg', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            
            $table->foreign('return_sale_invoice_id')->references('id')->on('returnSaleInvoice');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_return_adjustments');
    }
};