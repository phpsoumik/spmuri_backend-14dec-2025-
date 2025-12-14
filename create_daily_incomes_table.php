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
    $tableExists = Capsule::schema()->hasTable('daily_incomes');
    
    if ($tableExists) {
        echo "Table 'daily_incomes' already exists.\n";
    } else {
        // Create the daily_incomes table
        Capsule::schema()->create('daily_incomes', function ($table) {
            $table->id();
            $table->string('customer_name');
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->text('purpose');
            $table->timestamps();
        });
        
        echo "Table 'daily_incomes' created successfully!\n";
    }
    
    // Insert sample data
    $sampleExists = Capsule::table('daily_incomes')->count() > 0;
    
    if (!$sampleExists) {
        Capsule::table('daily_incomes')->insert([
            [
                'customer_name' => 'Sample Customer',
                'date' => date('Y-m-d'),
                'amount' => 1000.00,
                'purpose' => 'Sample daily income entry',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        echo "Sample data inserted successfully!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}