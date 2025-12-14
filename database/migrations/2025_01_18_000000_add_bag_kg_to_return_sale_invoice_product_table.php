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
        Schema::table('returnSaleInvoiceProduct', function (Blueprint $table) {
            $table->decimal('bag', 8, 2)->default(0)->after('taxAmount');
            $table->decimal('kg', 8, 2)->default(0)->after('bag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('returnSaleInvoiceProduct', function (Blueprint $table) {
            $table->dropColumn(['bag', 'kg']);
        });
    }
};