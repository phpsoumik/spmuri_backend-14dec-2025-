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
        Schema::table('customer', function (Blueprint $table) {
            $table->decimal('opening_advance_amount', 15, 2)->default(0)->after('last_due_amount');
            $table->text('opening_balance_note')->nullable()->after('opening_advance_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->dropColumn(['opening_advance_amount', 'opening_balance_note']);
        });
    }
};