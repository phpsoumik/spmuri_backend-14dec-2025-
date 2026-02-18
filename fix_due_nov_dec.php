<?php

$host = 'localhost';
$db = 'spmuri_live';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n\n";
    
    // Get November-December 2025 invoices
    $sql = "SELECT id, totalAmount, paidAmount, customer_current_due 
            FROM saleinvoice 
            WHERE YEAR(created_at) = 2025 
            AND MONTH(created_at) IN (11, 12)
            ORDER BY id";
    
    $stmt = $pdo->query($sql);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($invoices) . " invoices in November-December 2025\n\n";
    
    $updateStmt = $pdo->prepare("UPDATE saleinvoice SET dueAmount = ?, customer_current_due = ? WHERE id = ?");
    
    $updated = 0;
    foreach ($invoices as $invoice) {
        $correctDue = $invoice['totalAmount'] - $invoice['paidAmount'];
        
        $updateStmt->execute([$correctDue, $correctDue, $invoice['id']]);
        echo "Invoice ID {$invoice['id']}: Total={$invoice['totalAmount']}, Paid={$invoice['paidAmount']}, Due={$correctDue}\n";
        $updated++;
    }
    
    echo "\n✓ Successfully updated {$updated} invoices!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
