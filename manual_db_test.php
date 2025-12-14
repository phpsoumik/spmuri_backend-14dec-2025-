<?php

try {
    $pdo = new PDO('mysql:host=localhost;dbname=spmuri_backend', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Manual insert
    $sql = "INSERT INTO small_mc_products (item, item_name, amount, date, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['MANUAL001', 'Manual Test', 50.00, '2025-01-23']);
    
    if ($result) {
        echo "Manual insert SUCCESS!\n";
        
        // Check result
        $stmt = $pdo->query("SELECT * FROM small_mc_products ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Last inserted:\n";
        print_r($row);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}