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
        Schema::table('supplier', function (Blueprint $table) {
            $table->decimal('opening_due_amount', 15, 2)->default(0)->after('address');
            $table->decimal('opening_advance_amount', 15, 2)->default(0)->after('opening_due_amount');
            $table->decimal('current_due_amount', 15, 2)->default(0)->after('opening_advance_amount');
            $table->text('opening_balance_note')->nullable()->after('current_due_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->dropColumn(['opening_due_amount', 'opening_advance_amount', 'current_due_amount', 'opening_balance_note']);
        });
    }
};