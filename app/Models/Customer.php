<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customer';
    protected $primaryKey = 'id';
    protected $fillable = [
        'email',
        'phone',
        'address',
        'password',
        'roleId',
        'username',
        'googleId',
        'firstName',
        'lastName',
        'profileImage',
        'last_due_amount',
        'opening_advance_amount',
        'opening_balance_note',
        'current_due_amount',
    ];

    protected $casts = [
        'last_due_amount' => 'decimal:2',
        'opening_advance_amount' => 'decimal:2',
        'current_due_amount' => 'decimal:2',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'roleId');
    }

    public function saleInvoice(): HasMany
    {
        return $this->hasMany(SaleInvoice::class, 'customerId');
    }

    /**
     * Calculate and update current due amount
     * Formula: last_due_amount - opening_advance_amount
     */
    public function calculateCurrentDue()
    {
        $lastDue = $this->last_due_amount ?? 0;
        $openingAdvance = $this->opening_advance_amount ?? 0;
        $currentDue = $lastDue - $openingAdvance;
        
        $this->current_due_amount = $currentDue;
        return $currentDue;
    }
    
    /**
     * Get current due amount with real-time calculation
     * Calculates due per invoice including commission and bag values
     */
    public function getCurrentDueAttribute()
    {
        // Get opening balance and advance
        $openingBalance = $this->last_due_amount ?? 0;
        $openingAdvance = $this->opening_advance_amount ?? 0;
        
        // Get all invoices with details
        $invoices = DB::table('saleinvoice')
            ->where('customerId', $this->id)
            ->select('id', 'commission_value', 'bag_quantity', 'bag_price')
            ->get();
        
        if ($invoices->isEmpty()) {
            return round($openingBalance - $openingAdvance, 2);
        }
        
        $totalDue = 0;
        
        foreach ($invoices as $invoice) {
            // Get transaction amounts for this invoice
            $totalAmount = DB::table('transaction')
                ->where('type', 'sale')
                ->where('relatedId', $invoice->id)
                ->where('debitId', 4)
                ->sum('amount');
            
            $paidAmount = DB::table('transaction')
                ->where('type', 'sale')
                ->where('relatedId', $invoice->id)
                ->where('creditId', 4)
                ->sum('amount');
            
            $returnAmount = DB::table('transaction')
                ->where('type', 'sale_return')
                ->where('relatedId', $invoice->id)
                ->where('creditId', 4)
                ->sum('amount');
            
            $instantReturn = DB::table('transaction')
                ->where('type', 'sale_return')
                ->where('relatedId', $invoice->id)
                ->where('debitId', 4)
                ->sum('amount');
            
            // Calculate base due for this invoice
            $invoiceDue = (($totalAmount - $returnAmount) - $paidAmount) + $instantReturn;
            
            // Add commission and bag values
            $commissionValue = $invoice->commission_value ?? 0;
            $bagValue = ($invoice->bag_quantity ?? 0) * ($invoice->bag_price ?? 0);
            
            // Total due for this invoice
            $totalDue += $invoiceDue + $commissionValue + $bagValue;
        }
        
        // Final total = Opening Balance + All Invoice Dues - Opening Advance
        $finalDue = $openingBalance + $totalDue - $openingAdvance;
        
        return round($finalDue, 2);
    }

    /**
     * Get formatted due amount for display
     */
    public function getFormattedDueAttribute()
    {
        $due = $this->getCurrentDueAttribute();
        if ($due > 0) {
            return 'Due: Rs ' . number_format($due, 2);
        } elseif ($due < 0) {
            return 'Advance: Rs ' . number_format(abs($due), 2);
        }
        return 'Balanced';
    }

}
