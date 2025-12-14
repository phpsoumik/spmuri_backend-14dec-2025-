<?php

// Simple migration runner
require_once 'vendor/autoload.php';

// Load environment
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    $pdo = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database.\n";

    // Drop and recreate expenses table
    $pdo->exec("DROP TABLE IF EXISTS expenses");
    echo "Dropped existing expenses table.\n";

    // Create new expenses table
    $sql = "CREATE TABLE expenses (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        quantity_kg DECIMAL(8,3) NULL,
        rate_per_kg DECIMAL(8,2) NULL,
        date DATE NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    )";
    
    $pdo->exec($sql);
    echo "Created new expenses table successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>