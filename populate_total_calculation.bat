@echo off
echo Running migration to populate total_calculation for existing invoices...
php artisan migrate --path=database/migrations/2025_02_03_000001_populate_total_calculation_for_existing_invoices.php
echo Migration completed!
pause
