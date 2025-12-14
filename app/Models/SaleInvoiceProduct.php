<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleInvoiceProduct extends Model
{
    use HasFactory;
    protected $table = 'saleinvoiceproduct';
    protected $primaryKey = 'id';
    protected $fillable = [
        'productId',
        'ready_product_stock_item_id',
        'invoiceId',
        'productQuantity',
        'bag',
        'kg',
        'productUnitSalePrice',
        'productDiscount',
        'productFinalAmount',
        'tax',
        'taxAmount'
    ];

    public function saleInvoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class, 'invoiceId');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function readyProductStockItem(): BelongsTo
    {
        return $this->belongsTo(ReadyProductStockItem::class, 'ready_product_stock_item_id');
    }
}
