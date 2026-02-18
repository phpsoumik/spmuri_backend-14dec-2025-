<?php

$pdo = new PDO("mysql:host=localhost;dbname=spmuri_live;charset=utf8mb4", 'root', '');

$sql = "SELECT id, totalAmount, paidAmount, dueAmount, customer_current_due, customer_previous_due 
        FROM saleinvoice 
        WHERE id = 2334";

$stmt = $pdo->query($sql);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Invoice 2334 Data:\n";
echo "Total Amount: {$invoice['totalAmount']}\n";
echo "Paid Amount: {$invoice['paidAmount']}\n";
echo "Due Amount: {$invoice['dueAmount']}\n";
echo "Customer Previous Due: {$invoice['customer_previous_due']}\n";
echo "Customer Current Due: {$invoice['customer_current_due']}\n";
echo "\nCalculation:\n";
echo "customer_current_due - paidAmount = {$invoice['customer_current_due']} - {$invoice['paidAmount']} = " . ($invoice['customer_current_due'] - $invoice['paidAmount']) . "\n";
echo "totalAmount - paidAmount = {$invoice['totalAmount']} - {$invoice['paidAmount']} = " . ($invoice['totalAmount'] - $invoice['paidAmount']) . "\n";
