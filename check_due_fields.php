<?php

$pdo = new PDO("mysql:host=localhost;dbname=spmuri_live;charset=utf8mb4", 'root', '');

$sql = "SELECT id, totalAmount, paidAmount, dueAmount, customer_current_due 
        FROM saleinvoice 
        WHERE YEAR(created_at) = 2025 
        AND MONTH(created_at) = 11
        ORDER BY id DESC
        LIMIT 5";

$stmt = $pdo->query($sql);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sample November invoices:\n\n";
foreach ($invoices as $inv) {
    echo "ID: {$inv['id']}\n";
    echo "Total: {$inv['totalAmount']}, Paid: {$inv['paidAmount']}\n";
    echo "dueAmount: {$inv['dueAmount']}\n";
    echo "customer_current_due: {$inv['customer_current_due']}\n";
    echo "Correct Due: " . ($inv['totalAmount'] - $inv['paidAmount']) . "\n\n";
}
