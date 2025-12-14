<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ready_product_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ready_product_stock_id')->constrained('ready_product_stocks')->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained('purchase_products', 'id');
            $table->decimal('raw_quantity', 10, 3);
            $table->decimal('ready_quantity_kg', 10, 3);
            $table->integer('ready_quantity_bags')->default(0);
            $table->decimal('bags_weight_kg', 10, 3)->default(0);
            $table->decimal('remaining_kg', 10, 3)->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('conversion_ratio', 5, 3)->default(1);
            $table->string('ready_product_name')->default('Ready Product');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ready_product_stock_items');
    }
};