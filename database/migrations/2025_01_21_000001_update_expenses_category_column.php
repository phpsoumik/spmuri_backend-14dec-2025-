<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Change category from enum to varchar to allow any category
            $table->string('category', 100)->change();
            
            // Add new columns if they don't exist
            if (!Schema::hasColumn('expenses', 'quantity_kg')) {
                $table->decimal('quantity_kg', 8, 3)->nullable();
            }
            if (!Schema::hasColumn('expenses', 'rate_per_kg')) {
                $table->decimal('rate_per_kg', 8, 2)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Revert back to enum (optional)
            $table->enum('category', ['wood_dust', 'wood', 'white_sand', 'labour', 'electricity', 'other'])->change();
            
            // Drop the new columns
            $table->dropColumn(['quantity_kg', 'rate_per_kg']);
        });
    }
};