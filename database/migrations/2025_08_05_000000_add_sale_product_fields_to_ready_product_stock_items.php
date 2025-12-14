<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ready_product_stock_items', function (Blueprint $table) {
            $table->foreignId('sale_product_id')->nullable()->constrained('products', 'id')->after('raw_material_id');
            $table->string('sale_product_name')->nullable()->after('sale_product_id');
        });
    }

    public function down()
    {
        Schema::table('ready_product_stock_items', function (Blueprint $table) {
            $table->dropForeign(['sale_product_id']);
            $table->dropColumn(['sale_product_id', 'sale_product_name']);
        });
    }
};