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
        Schema::table('purchaseInvoice', function (Blueprint $table) {
            $table->json('commissions')->nullable()->after('note');
            $table->decimal('total_commission_amount', 10, 2)->default(0.00)->after('commissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchaseInvoice', function (Blueprint $table) {
            $table->dropColumn(['commissions', 'total_commission_amount']);
        });
    }
};