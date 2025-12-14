<?php

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Database connection
    $pdo = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.\n";

    // Fix the expenses table
    $sql = "ALTER TABLE expenses 
            MODIFY COLUMN category VARCHAR(100) NOT NULL,
            ADD COLUMN IF NOT EXISTS quantity_kg DECIMAL(8,3) NULL,
            ADD COLUMN IF NOT EXISTS rate_per_kg DECIMAL(8,2) NULL";
    
    $pdo->exec($sql);
    echo "Expenses table updated successfully!\n";
    echo "- Category column changed to VARCHAR(100)\n";
    echo "- Added quantity_kg column\n";
    echo "- Added rate_per_kg column\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>