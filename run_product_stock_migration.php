<?php

require_once __DIR__ . '/vendor/autoload.php';

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
    // Check if columns already exist
    $columns = Capsule::select("SHOW COLUMNS FROM product LIKE 'current_%'");
    
    if (empty($columns)) {
        echo "Adding current stock fields to product table...\n";
        
        Capsule::statement("
            ALTER TABLE product 
            ADD COLUMN current_bags DECIMAL(10,2) DEFAULT 0 AFTER productQuantity,
            ADD COLUMN current_stock_kg DECIMAL(10,2) DEFAULT 0 AFTER current_bags
        ");
        
        echo "✅ Successfully added current_bags and current_stock_kg fields to product table\n";
    } else {
        echo "✅ Current stock fields already exist in product table\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}