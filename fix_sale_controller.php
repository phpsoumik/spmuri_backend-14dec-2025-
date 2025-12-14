<?php
// Quick fix for SaleInvoiceController.php
// Replace all occurrences of this line:

// OLD CODE:
// $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
// $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);

// NEW CODE (add these 2 lines BEFORE the above lines):
// $item->paidAmount = takeUptoThreeDecimal($totalPaid);
// $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);

// MANUAL STEPS:
// 1. Open SaleInvoiceController.php
// 2. Find all occurrences of: $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
// 3. Add these 2 lines BEFORE each occurrence:
//    $item->paidAmount = takeUptoThreeDecimal($totalPaid);
//    $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);

// There are approximately 4-5 places where this needs to be added
// Look for the map function sections in getAllSaleInvoice method