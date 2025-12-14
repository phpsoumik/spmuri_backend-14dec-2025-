@echo off
cd /d "d:\xampp\htdocs\SPMURI_BACKEND"
php artisan migrate --path=database/migrations/2025_01_23_000000_add_date_to_small_mc_products_table.php
pause