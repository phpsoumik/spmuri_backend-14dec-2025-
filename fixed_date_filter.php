<?php
// Initialize variables for date filter
$date_filter = "";
$from = '';
$to = '';
if (isset($_POST['button1']) && isset($_POST['button2'])) {
    $from = $_POST['button1'];
    $to = $_POST['button2'];
    
    // Add time to include full end date
    $from_datetime = $from . ' 00:00:00';
    $to_datetime = $to . ' 23:59:59';
    
    $date_filter = "WHERE po.date_created BETWEEN '$from_datetime' AND '$to_datetime'";
}

// Rest of your code remains the same...
?>