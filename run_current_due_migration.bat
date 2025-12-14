@echo off
echo Running Customer Current Due Migration...
echo.

cd /d "d:\xampp\htdocs\SPMURI_BACKEND"

echo Step 1: Running Laravel Migration...
php artisan migrate --path=database/migrations/2025_01_31_000000_add_current_due_amount_to_customers_table.php

echo.
echo Step 2: Running Custom Migration Script...
php run_current_due_migration.php

echo.
echo Migration completed!
pause