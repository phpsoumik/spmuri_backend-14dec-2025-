@echo off
echo Running Net Weight Migration...
php artisan migrate --path=database/migrations/2025_11_04_000000_add_net_weight_to_purchase_invoice_product_table.php
echo Migration completed!
pause