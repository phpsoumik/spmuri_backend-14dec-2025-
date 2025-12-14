<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SaleReturnAdjustment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sale_return_adjustments';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'return_sale_invoice_id',
        'adjustment_type',
        'cash_refund_amount',
        'exchange_product_id',
        'exchange_quantity',
        'exchange_bag',
        'exchange_kg',
        'notes',
        'status'
    ];

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($model) {
            $lastAdjustment = static::orderBy('created_at', 'desc')->first();
            $nextNumber = $lastAdjustment ? (int)substr($lastAdjustment->id, -6) + 1 : 1;
            $model->id = 'ADJ_' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    public function returnSaleInvoice(): BelongsTo
    {
        return $this->belongsTo(ReturnSaleInvoice::class, 'return_sale_invoice_id');
    }

    public function exchangeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'exchange_product_id');
    }
}