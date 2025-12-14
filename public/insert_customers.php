<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database connection
$host = env('DB_HOST', 'localhost');
$port = env('DB_PORT', '3306');
$database = env('DB_DATABASE', 'forge');
$username = env('DB_USERNAME', 'forge');
$password = env('DB_PASSWORD', '');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.<br>";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/insert_customers.sql');
    
    // Execute SQL
    $pdo->exec($sql);
    
    echo "Customers inserted successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}