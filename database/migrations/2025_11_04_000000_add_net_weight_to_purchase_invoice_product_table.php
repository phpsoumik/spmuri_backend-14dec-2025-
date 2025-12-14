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
        Schema::table('purchaseinvoiceproduct', function (Blueprint $table) {
            $table->decimal('netWeight', 10, 2)->default(0)->after('tareWeight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchaseinvoiceproduct', function (Blueprint $table) {
            $table->dropColumn('netWeight');
        });
    }
};