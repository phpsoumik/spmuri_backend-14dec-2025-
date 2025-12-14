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
        Schema::table('saleinvoice', function (Blueprint $table) {
            $table->decimal('customer_previous_due', 10, 2)->default(0)->after('customerId');
            $table->decimal('customer_current_due', 10, 2)->default(0)->after('customer_previous_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saleinvoice', function (Blueprint $table) {
            $table->dropColumn(['customer_previous_due', 'customer_current_due']);
        });
    }
};