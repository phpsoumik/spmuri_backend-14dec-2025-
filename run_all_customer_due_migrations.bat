@echo off
echo Running All Customer Due Related Migrations...
echo.

cd /d "d:\xampp\htdocs\SPMURI_BACKEND"

echo Step 1: Running Customer current_due_amount Migration...
php artisan migrate --path=database/migrations/2025_01_31_000000_add_current_due_amount_to_customers_table.php

echo.
echo Step 2: Running SaleInvoice customer_due fields Migration...
php artisan migrate --path=database/migrations/2025_11_03_000001_add_customer_due_to_sale_invoice_table.php

echo.
echo Step 3: Running Custom Update Script...
php run_current_due_migration.php

echo.
echo All migrations completed successfully!
echo.
echo Now you can test:
echo 1. Add/Edit customers with last due and advance amounts
echo 2. Create sales and see customer due amounts update
echo 3. Make payments and see customer due amounts reduce
echo.
pause