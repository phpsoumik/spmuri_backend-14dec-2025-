<?php

// Direct database test for Small M/C Products
require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "Testing Small M/C Product Database...\n\n";

try {
    // Test database connection
    $pdo = new PDO('mysql:host=localhost;dbname=erp_laravel', 'root', '');
    echo "✓ Database connection successful\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'small_mc_products'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'small_mc_products' exists\n";
        
        // Get current data
        $stmt = $pdo->query("SELECT * FROM small_mc_products ORDER BY created_at DESC LIMIT 5");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✓ Current products in database: " . count($products) . "\n";
        
        if (count($products) > 0) {
            echo "\nExisting products:\n";
            foreach ($products as $product) {
                echo "- ID: {$product['id']}, Item: {$product['item']}, Name: {$product['item_name']}, Amount: {$product['amount']}\n";
            }
        }
        
        // Test insert
        $stmt = $pdo->prepare("INSERT INTO small_mc_products (item, item_name, amount, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $result = $stmt->execute(['TEST001', 'Test Product', 100.50]);
        
        if ($result) {
            echo "✓ Test insert successful\n";
            $lastId = $pdo->lastInsertId();
            echo "✓ New product ID: $lastId\n";
            
            // Clean up test data
            $pdo->prepare("DELETE FROM small_mc_products WHERE id = ?")->execute([$lastId]);
            echo "✓ Test data cleaned up\n";
        }
        
    } else {
        echo "✗ Table 'small_mc_products' does not exist\n";
        echo "Run: php artisan migrate --path=database/migrations/2025_01_22_000000_create_small_mc_products_table.php\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
?>