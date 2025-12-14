<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Update the enum to include 'sub' type
        DB::statement("ALTER TABLE b2b_companies MODIFY COLUMN type ENUM('main', 'independent', 'sub') DEFAULT 'independent'");
    }

    public function down()
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE b2b_companies MODIFY COLUMN type ENUM('main', 'independent') DEFAULT 'independent'");
    }
};