<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ready_product_stock_items', function (Blueprint $table) {
            $table->decimal('current_stock_kg', 10, 3)->default(0)->after('ready_quantity_kg');
            $table->integer('current_stock_bags')->default(0)->after('current_stock_kg');
        });
    }

    public function down()
    {
        Schema::table('ready_product_stock_items', function (Blueprint $table) {
            $table->dropColumn(['current_stock_kg', 'current_stock_bags']);
        });
    }
};