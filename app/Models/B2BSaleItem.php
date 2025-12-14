<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BSaleItem extends Model
{
    use HasFactory;

    protected $table = 'b2b_sale_items';

    protected $fillable = [
        'b2b_sale_id',
        'ready_product_stock_item_id',
        'product_description',
        'quantity_kg',
        'bags',
        'uom',
        'rate_per_kg',
        'total_amount',
        'hsn_code'
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:3',
        'rate_per_kg' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    // B2B Sale relationship
    public function b2bSale(): BelongsTo
    {
        return $this->belongsTo(B2BSale::class, 'b2b_sale_id');
    }

    // Ready product stock item relationship
    public function readyProductStockItem(): BelongsTo
    {
        return $this->belongsTo(ReadyProductStockItem::class, 'ready_product_stock_item_id');
    }
}