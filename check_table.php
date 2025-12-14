<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=spmuri_backend', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE small_mc_products");
    echo "Table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
    echo "\n\nTesting direct insert:\n";
    
    // Test direct insert
    $stmt = $pdo->prepare("INSERT INTO small_mc_products (item, item_name, amount, date) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute(['TEST999', 'Test Direct', 99.99, '2025-01-23']);
    
    if ($result) {
        echo "Direct insert successful!\n";
        
        // Check what was saved
        $stmt = $pdo->query("SELECT * FROM small_mc_products WHERE item = 'TEST999' ORDER BY id DESC LIMIT 1");
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Saved data:\n";
        print_r($product);
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}