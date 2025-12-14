<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=spmuri_backend', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test insert with date
    $stmt = $pdo->prepare("INSERT INTO small_mc_products (item, item_name, amount, date) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute(['TEST001', 'Test Product', 100.50, '2025-01-23']);
    
    if ($result) {
        echo "Product inserted successfully with date!\n";
        
        // Check what was saved
        $stmt = $pdo->prepare("SELECT * FROM small_mc_products WHERE item = 'TEST001' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Saved data:\n";
        print_r($product);
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}