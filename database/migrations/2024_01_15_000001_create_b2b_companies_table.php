<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('b2b_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['main', 'independent'])->default('independent');
            $table->unsignedBigInteger('parent_id')->nullable(); // For sub-companies
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number', 15)->nullable();
            $table->string('state')->nullable();
            $table->string('state_code', 2)->nullable();
            $table->string('pin_code', 10)->nullable();
            $table->string('hsn_code')->nullable();
            $table->decimal('gst_rate', 5, 2)->default(5.00);
            $table->string('vehicle_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('b2b_companies')->onDelete('cascade');
            $table->index(['type', 'parent_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('b2b_companies');
    }
};