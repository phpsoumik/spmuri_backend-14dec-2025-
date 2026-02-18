<?php

$pdo = new PDO("mysql:host=localhost;dbname=spmuri_live;charset=utf8mb4", 'root', '');
$stmt = $pdo->query("DESCRIBE saleinvoice");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $columns);
