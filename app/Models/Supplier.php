<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'supplier';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'phone',
        'address',
        'email',
        'opening_due_amount',
        'opening_advance_amount',
        'current_due_amount',
        'opening_balance_note',
    ];

    protected $casts = [
        'opening_due_amount' => 'decimal:2',
        'opening_advance_amount' => 'decimal:2',
        'current_due_amount' => 'decimal:2',
    ];

    public function purchaseInvoice(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplierId');
    }

    /**
     * Calculate current due amount for supplier
     */
    public function calculateCurrentDue()
    {
        // Get all purchase invoices total due
        $purchaseDue = $this->purchaseInvoice()->sum('dueAmount') ?? 0;
        
        // Calculate: Opening Due + Purchase Due - Opening Advance
        $currentDue = ($this->opening_due_amount ?? 0) + $purchaseDue - ($this->opening_advance_amount ?? 0);
        
        // Update current due amount
        $this->current_due_amount = $currentDue;
        $this->save();
        
        return $currentDue;
    }

    /**
     * Get formatted due amount for display
     */
    public function getFormattedDueAttribute()
    {
        $due = $this->current_due_amount;
        if ($due > 0) {
            return 'Due: Rs ' . number_format($due, 2);
        } elseif ($due < 0) {
            return 'Advance: Rs ' . number_format(abs($due), 2);
        }
        return 'Balanced';
    }
}
