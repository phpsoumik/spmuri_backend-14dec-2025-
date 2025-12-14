<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Setup database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    echo "Running current_due_amount migration...\n";
    
    // Check if column already exists
    $columnExists = Capsule::schema()->hasColumn('customer', 'current_due_amount');
    
    if (!$columnExists) {
        // Add current_due_amount column
        Capsule::schema()->table('customer', function ($table) {
            $table->decimal('current_due_amount', 15, 2)->default(0)->after('opening_balance_note');
        });
        echo "✓ Added current_due_amount column to customer table\n";
        
        // Update existing customers with calculated current_due_amount
        $customers = Capsule::table('customer')->get();
        
        foreach ($customers as $customer) {
            $lastDue = $customer->last_due_amount ?? 0;
            $advance = $customer->opening_advance_amount ?? 0;
            $currentDue = $lastDue - $advance;
            
            Capsule::table('customer')
                ->where('id', $customer->id)
                ->update(['current_due_amount' => $currentDue]);
        }
        
        echo "✓ Updated " . count($customers) . " customers with calculated current_due_amount\n";
        echo "✓ Migration completed successfully!\n";
    } else {
        echo "✓ current_due_amount column already exists\n";
        
        // Still update existing customers
        $customers = Capsule::table('customer')->get();
        
        foreach ($customers as $customer) {
            $lastDue = $customer->last_due_amount ?? 0;
            $advance = $customer->opening_advance_amount ?? 0;
            $currentDue = $lastDue - $advance;
            
            Capsule::table('customer')
                ->where('id', $customer->id)
                ->update(['current_due_amount' => $currentDue]);
        }
        
        echo "✓ Updated " . count($customers) . " customers with calculated current_due_amount\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}