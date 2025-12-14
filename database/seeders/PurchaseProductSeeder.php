<?php

namespace Database\Seeders;

use App\Models\PurchaseProduct;
use Illuminate\Database\Seeder;

class PurchaseProductSeeder extends Seeder
{
    public function run()
    {
        $purchaseProducts = [
            [
                'name' => 'Rice Premium',
                'purchase_price' => 45.50,
                'sku' => 'PP001',
                'description' => 'Premium quality rice for purchase',
                'status' => true
            ],
            [
                'name' => 'Wheat Flour',
                'purchase_price' => 35.00,
                'sku' => 'PP002',
                'description' => 'High quality wheat flour',
                'status' => true
            ],
            [
                'name' => 'Sugar White',
                'purchase_price' => 55.75,
                'sku' => 'PP003',
                'description' => 'Pure white sugar',
                'status' => true
            ]
        ];

        foreach ($purchaseProducts as $product) {
            PurchaseProduct::create($product);
        }
    }
}