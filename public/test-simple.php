<?php
header('Access-Control-Allow-Origin: https://dh3hlzph-3000.inc1.devtunnels.ms');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Simple test endpoint',
    'time' => date('Y-m-d H:i:s')
]);
?>