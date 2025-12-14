<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('b2b_sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('b2b_sale_id');
            $table->unsignedBigInteger('ready_product_stock_item_id');
            $table->string('product_description');
            $table->decimal('quantity_kg', 10, 3);
            $table->integer('bags');
            $table->string('uom', 10)->default('KG');
            $table->decimal('rate_per_kg', 10, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('hsn_code')->nullable();
            $table->timestamps();
            
            $table->foreign('b2b_sale_id')->references('id')->on('b2b_sales')->onDelete('cascade');
            $table->foreign('ready_product_stock_item_id')->references('id')->on('ready_product_stock_items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('b2b_sale_items');
    }
};