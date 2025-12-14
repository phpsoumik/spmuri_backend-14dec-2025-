<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleInvoice extends Model
{
    use HasFactory;

    protected $table = 'saleinvoice';
    protected $primaryKey = 'id';
    public $incrementing = true; // Enable auto increment
    protected $keyType = 'int'; // Change back to int

    protected $fillable = [
        'date',
        'invoiceMemoNo',
        'totalAmount',
        'totalTaxAmount',
        'totalDiscountAmount',
        'paidAmount',
        'dueAmount',
        'profit',
        'customerId',
        'customer_previous_due',
        'customer_current_due',
        'note',
        'address',
        'commission_type',
        'commission_value',
        'bag_quantity',
        'bag_price',
        'dueDate',
        'termsAndConditions',
        'userId',
        'isHold',
        'orderStatus',
        'subtotal',
        'cgst_rate',
        'sgst_rate',
        'cgst_amount',
        'sgst_amount',
        'total_gst',
        'grand_total',
        'gst_applicable',
    ];
    
    protected $casts = [
        'customer_previous_due' => 'decimal:2',
        'customer_current_due' => 'decimal:2',
    ];



    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::class, 'userId');
    }

    public function saleInvoiceProduct(): HasMany
    {
        return $this->hasMany(SaleInvoiceProduct::class, 'invoiceId');
    }

    public function returnSaleInvoice(): HasMany
    {
        return $this->hasMany(ReturnSaleInvoice::class, 'saleInvoiceId');
    }

    public function saleInvoiceVat(): HasMany
    {
        return $this->hasMany(SaleInvoiceVat::class, 'invoiceId');
    }
}
