<?php
// Run this file to setup purchase products
echo "Setting up Purchase Products...\n";

// Run migration
echo "Running migration...\n";
exec('php artisan migrate --path=database/migrations/2024_12_19_000000_create_purchase_products_table.php', $output1);
print_r($output1);

// Run seeder
echo "Running seeder...\n";
exec('php artisan db:seed --class=PurchaseProductSeeder', $output2);
print_r($output2);

echo "Setup completed!\n";
?>