<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->text('purpose');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_incomes');
    }
};