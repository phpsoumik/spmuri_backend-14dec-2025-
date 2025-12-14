<?php

// Simple debug script to see what data is coming
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Request data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Raw input: " . file_get_contents('php://input') . "\n", FILE_APPEND);

echo "Debug logged";
?>