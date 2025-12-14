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
        Schema::table('purchase_invoice', function (Blueprint $table) {
            $table->decimal('supplier_previous_due', 15, 2)->default(0)->after('supplierId');
            $table->decimal('supplier_current_due', 15, 2)->default(0)->after('supplier_previous_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoice', function (Blueprint $table) {
            $table->dropColumn(['supplier_previous_due', 'supplier_current_due']);
        });
    }
};