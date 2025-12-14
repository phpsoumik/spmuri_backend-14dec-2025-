<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('b2b_sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('invoice_no')->unique();
            $table->string('order_no')->nullable();
            $table->unsignedBigInteger('main_company_id'); // Main company
            $table->unsignedBigInteger('sub_company_id')->nullable(); // Sub company (if any)
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(2.5);
            $table->decimal('sgst_rate', 5, 2)->default(2.5);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('total_gst', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);
            $table->string('vehicle_number')->nullable();
            $table->text('amount_in_words')->nullable();
            $table->enum('payment_terms', ['cash', 'credit_7', 'credit_15', 'credit_30'])->default('cash');
            $table->date('due_date')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('main_company_id')->references('id')->on('b2b_companies');
            $table->foreign('sub_company_id')->references('id')->on('b2b_companies');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('b2b_sales');
    }
};