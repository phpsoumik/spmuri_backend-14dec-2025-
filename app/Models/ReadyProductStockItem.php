<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadyProductStockItem extends Model
{
    use HasFactory;

    protected $table = 'ready_product_stock_items';

    protected $fillable = [
        'ready_product_stock_id',
        'raw_material_id',
        'sale_product_id',
        'sale_product_name',
        'raw_quantity',
        'ready_quantity_kg',
        'current_stock_kg',
        'ready_quantity_bags',
        'current_stock_bags',
        'bags_weight_kg',
        'remaining_kg',
        'unit_price',
        'total_price',
        'conversion_ratio',
        'ready_product_name'
    ];

    protected $casts = [
        'raw_quantity' => 'decimal:3',
        'ready_quantity_kg' => 'decimal:3',
        'current_stock_kg' => 'decimal:3',
        'ready_quantity_bags' => 'integer',
        'current_stock_bags' => 'integer',
        'bags_weight_kg' => 'decimal:3',
        'remaining_kg' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'conversion_ratio' => 'decimal:3'
    ];

    public function readyProductStock()
    {
        return $this->belongsTo(ReadyProductStock::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(PurchaseProduct::class, 'raw_material_id');
    }

    public function saleProduct()
    {
        return $this->belongsTo(Product::class, 'sale_product_name');
    }
}