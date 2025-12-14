<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentPurchaseInvoice extends Model
{
    use HasFactory;

    protected $table = 'transaction';
    
    protected $fillable = [
        'date',
        'debitId',
        'creditId', 
        'amount',
        'particulars',
        'type',
        'relatedId'
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'decimal:2'
    ];

    // Relationship with PurchaseInvoice
    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'relatedId');
    }

    // Relationship with Account (debit)
    public function debitAccount()
    {
        return $this->belongsTo(Account::class, 'debitId');
    }

    // Relationship with Account (credit)  
    public function creditAccount()
    {
        return $this->belongsTo(Account::class, 'creditId');
    }
}