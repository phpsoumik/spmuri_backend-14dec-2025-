<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2BSale extends Model
{
    use HasFactory;

    protected $table = 'b2b_sales';

    protected $fillable = [
        'date',
        'invoice_no',
        'order_no',
        'main_company_id',
        'sub_company_id',
        'subtotal',
        'cgst_rate',
        'sgst_rate',
        'cgst_amount',
        'sgst_amount',
        'total_gst',
        'grand_total',
        'paid_amount',
        'due_amount',
        'vehicle_number',
        'amount_in_words',
        'payment_terms',
        'due_date',
        'note',
        'status',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'cgst_rate' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'total_gst' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2'
    ];

    // Main company relationship
    public function mainCompany(): BelongsTo
    {
        return $this->belongsTo(B2BCompany::class, 'main_company_id');
    }

    // Sub company relationship
    public function subCompany(): BelongsTo
    {
        return $this->belongsTo(B2BCompany::class, 'sub_company_id');
    }

    // Sale items relationship
    public function items(): HasMany
    {
        return $this->hasMany(B2BSaleItem::class, 'b2b_sale_id');
    }

    // Created by user relationship
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Users::class, 'created_by');
    }

    // Generate next invoice number
    public static function generateInvoiceNumber()
    {
        $lastInvoice = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice ? (int)$lastInvoice->invoice_no + 1 : 1;
        return str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}