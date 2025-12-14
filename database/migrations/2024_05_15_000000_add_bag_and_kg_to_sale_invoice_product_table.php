<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('saleInvoiceProduct', function (Blueprint $table) {
            $table->decimal('bag', 10, 2)->default(0)->after('productQuantity');
            $table->decimal('kg', 10, 2)->default(0)->after('bag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saleInvoiceProduct', function (Blueprint $table) {
            $table->dropColumn('bag');
            $table->dropColumn('kg');
        });
    }
};