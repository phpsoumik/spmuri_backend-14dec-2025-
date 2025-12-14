<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->decimal('bag_quantity', 10, 2)->default(0)->after('commission_value');
            $table->decimal('bag_price', 10, 2)->default(0)->after('bag_quantity');
        });
    }

    public function down()
    {
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->dropColumn(['bag_quantity', 'bag_price']);
        });
    }
};
