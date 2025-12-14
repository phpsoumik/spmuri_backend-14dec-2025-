<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('daily_incomes', function (Blueprint $table) {
            $table->text('purpose')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('daily_incomes', function (Blueprint $table) {
            $table->text('purpose')->nullable(false)->change();
        });
    }
};