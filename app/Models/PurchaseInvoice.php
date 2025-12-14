<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'purchaseinvoice';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'totalAmount',
        'totalTax',
        'paidAmount',
        'dueAmount',
        'supplierId',
        'supplier_previous_due',
        'supplier_current_due',
        'note',
        'supplierMemoNo',
        'invoiceMemoNo',
        'lorryNo',
        'commissions',
        'total_commission_amount',
    ];

    protected $casts = [
        'commissions' => 'array',
        'total_commission_amount' => 'decimal:2',
        'supplier_previous_due' => 'decimal:2',
        'supplier_current_due' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            // Get the next sequential number
            $lastInvoice = static::orderBy('created_at', 'desc')->first();
            $nextNumber = $lastInvoice ? (int)$lastInvoice->id + 1 : 1;
            $model->id = (string)$nextNumber;
        });
    }



    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function returnPurchaseInvoice(): HasMany
    {
        return $this->hasMany(ReturnPurchaseInvoice::class, 'purchaseInvoiceId');
    }

    public function purchaseInvoiceProduct(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceProduct::class, 'invoiceId');
    }

    public function paymentPurchaseInvoice(): HasMany
    {
        return $this->hasMany(PaymentPurchaseInvoice::class, 'purchaseInvoiceId');
    }
}
