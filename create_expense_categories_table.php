<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database configuration
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'] ?? 'spmuri_pos',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    // Check if table exists
    $tableExists = Capsule::schema()->hasTable('expense_categories');
    
    if ($tableExists) {
        echo "Table 'expense_categories' already exists.\n";
    } else {
        // Create the expense_categories table
        Capsule::schema()->create('expense_categories', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('value');
            $table->boolean('status')->default(true);
            $table->timestamps();
            
            $table->unique('name');
            $table->unique('value');
        });
        
        echo "Table 'expense_categories' created successfully!\n";
    }
    
    // Insert default categories
    $categoriesExist = Capsule::table('expense_categories')->count() > 0;
    
    if (!$categoriesExist) {
        $defaultCategories = [
            ['name' => 'Wood Dust', 'value' => 'wood_dust'],
            ['name' => 'Wood', 'value' => 'wood'],
            ['name' => 'White Sand', 'value' => 'white_sand'],
            ['name' => 'Labour', 'value' => 'labour'],
            ['name' => 'Electricity', 'value' => 'electricity'],
            ['name' => 'Labour Tiffin', 'value' => 'labour_tiffin'],
            ['name' => 'Owner Consumption', 'value' => 'owner_consumption'],
            ['name' => 'Other', 'value' => 'other']
        ];
        
        foreach ($defaultCategories as $category) {
            Capsule::table('expense_categories')->insert([
                'name' => $category['name'],
                'value' => $category['value'],
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        echo "Default categories inserted successfully!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}