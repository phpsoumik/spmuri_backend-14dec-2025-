<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceProduct extends Model
{
    use HasFactory;
    protected $table = 'purchaseinvoiceproduct';
    protected $primaryKey = 'id';
    protected $fillable = [
        'invoiceId',
        'productId',
        'productQuantity',
        'bag',
        'kg',
        'grossWeight',
        'tareWeight',
        'netWeight',
        'productUnitPurchasePrice',
        'productFinalAmount',
        'tax',
        'taxAmount'
    ];
    
    protected $casts = [
        'invoiceId' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'invoiceId');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(PurchaseProduct::class, 'productId');
    }
}
