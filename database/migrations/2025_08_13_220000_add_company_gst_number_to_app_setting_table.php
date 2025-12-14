<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appSetting', function (Blueprint $table) {
            $table->string('company_gst_number', 15)->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('appSetting', function (Blueprint $table) {
            $table->dropColumn('company_gst_number');
        });
    }
};