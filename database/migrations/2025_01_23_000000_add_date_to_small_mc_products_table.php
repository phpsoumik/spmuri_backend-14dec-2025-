<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('small_mc_products', function (Blueprint $table) {
            $table->date('date')->nullable()->after('amount');
        });
    }

    public function down()
    {
        Schema::table('small_mc_products', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};