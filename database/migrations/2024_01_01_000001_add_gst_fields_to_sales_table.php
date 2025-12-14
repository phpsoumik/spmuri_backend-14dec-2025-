<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->nullable()->after('totalAmount');
            $table->decimal('cgst_rate', 5, 2)->default(2.5)->after('subtotal');
            $table->decimal('sgst_rate', 5, 2)->default(2.5)->after('cgst_rate');
            $table->decimal('cgst_amount', 10, 2)->default(0)->after('sgst_rate');
            $table->decimal('sgst_amount', 10, 2)->default(0)->after('cgst_amount');
            $table->decimal('total_gst', 10, 2)->default(0)->after('sgst_amount');
            $table->decimal('grand_total', 10, 2)->nullable()->after('total_gst');
            $table->boolean('gst_applicable')->default(true)->after('grand_total');
        });
    }

    public function down()
    {
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal', 'cgst_rate', 'sgst_rate', 'cgst_amount', 
                'sgst_amount', 'total_gst', 'grand_total', 'gst_applicable'
            ]);
        });
    }
};