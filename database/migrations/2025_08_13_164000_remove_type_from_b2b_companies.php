<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('b2b_companies', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down()
    {
        Schema::table('b2b_companies', function (Blueprint $table) {
            $table->enum('type', ['main', 'independent'])->default('independent');
        });
    }
};