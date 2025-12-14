<?php

// Run database migrations for ready product stock tables
require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Database configuration
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
    // Create ready_product_stocks table
    if (!Capsule::schema()->hasTable('ready_product_stocks')) {
        Capsule::schema()->create('ready_product_stocks', function ($table) {
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
        echo "✓ ready_product_stocks table created successfully\n";
    } else {
        echo "✓ ready_product_stocks table already exists\n";
    }

    // Create ready_product_stock_items table
    if (!Capsule::schema()->hasTable('ready_product_stock_items')) {
        Capsule::schema()->create('ready_product_stock_items', function ($table) {
            $table->id();
            $table->foreignId('ready_product_stock_id')->constrained('ready_product_stocks')->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained('purchase_product', 'id');
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
        echo "✓ ready_product_stock_items table created successfully\n";
    } else {
        echo "✓ ready_product_stock_items table already exists\n";
    }

    echo "\n✅ All migrations completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>