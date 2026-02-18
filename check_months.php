<?php

$pdo = new PDO("mysql:host=localhost;dbname=spmuri_live;charset=utf8mb4", 'root', '');
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM saleinvoice GROUP BY month ORDER BY month DESC LIMIT 10");
$months = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent months with invoices:\n";
foreach ($months as $m) {
    echo "{$m['month']}: {$m['count']} invoices\n";
}
