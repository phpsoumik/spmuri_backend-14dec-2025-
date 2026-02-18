@echo off
echo Running total_calculation migration...
php artisan migrate --path=database/migrations/2025_02_03_000000_add_total_calculation_to_sale_invoice_table.php
echo Migration completed!
pause
