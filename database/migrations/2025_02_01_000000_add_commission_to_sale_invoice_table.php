<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->string('commission_type')->nullable()->after('address');
            $table->decimal('commission_value', 10, 2)->default(0)->after('commission_type');
        });
    }

    public function down()
    {
        Schema::table('saleInvoice', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_value']);
        });
    }
};
