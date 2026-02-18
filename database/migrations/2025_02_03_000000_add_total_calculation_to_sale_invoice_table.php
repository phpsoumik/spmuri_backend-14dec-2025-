<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saleinvoice', function (Blueprint $table) {
            $table->decimal('total_calculation', 10, 3)->default(0)->after('totalAmount');
        });
    }

    public function down(): void
    {
        Schema::table('saleinvoice', function (Blueprint $table) {
            $table->dropColumn('total_calculation');
        });
    }
};
