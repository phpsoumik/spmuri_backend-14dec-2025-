<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'erp_laravel',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    // Drop and recreate ready_product_stock_items table without foreign key constraints
    Capsule::schema()->dropIfExists('ready_product_stock_items');
    
    Capsule::schema()->create('ready_product_stock_items', function ($table) {
        $table->id();
        $table->unsignedBigInteger('ready_product_stock_id');
        $table->unsignedBigInteger('raw_material_id');
        $table->decimal('raw_quantity', 10, 3);
        $table->decimal('ready_quantity_kg', 10, 3);
        $table->integer('ready_quantity_bags')->default(0);
        $table->decimal('bags_weight_kg', 10, 3)->default(0);
        $table->decimal('remaining_kg', 10, 3)->default(0);
        $table->decimal('unit_price', 10, 2);
        $table->decimal('total_price', 15, 2);
        $table->decimal('conversion_ratio', 5, 3)->default(1);
        $table->string('ready_product_name')->default('Ready Product');
        $table->timestamps();
    });
    
    echo "✅ ready_product_stock_items table recreated without foreign key constraints!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>