<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'purchase_price',
        'sku',
        'description',
        'status'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'status' => 'boolean'
    ];

    public function purchaseInvoiceProducts(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceProduct::class, 'productId');
    }
}