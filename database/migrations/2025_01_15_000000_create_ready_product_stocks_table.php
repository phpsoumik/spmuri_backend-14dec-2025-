<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ready_product_stocks', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_ready_product_kg', 10, 3)->default(0);
            $table->integer('total_bags')->default(0);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ready_product_stocks');
    }
};