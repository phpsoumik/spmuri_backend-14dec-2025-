<?php

// Add date column to small_mc_products table
try {
    $pdo = new PDO('mysql:host=localhost;dbname=spmuri_backend', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if date column already exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM small_mc_products LIKE 'date'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Add date column
        $sql = "ALTER TABLE small_mc_products ADD COLUMN date DATE NULL AFTER amount";
        $pdo->exec($sql);
        echo "Date column added successfully to small_mc_products table.\n";
    } else {
        echo "Date column already exists in small_mc_products table.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}