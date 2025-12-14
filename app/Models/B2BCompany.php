<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BCompany extends Model
{
    use HasFactory;

    protected $table = 'b2b_companies';

    protected $fillable = [
        'name',
        'parent_id',
        'contact_person',
        'phone',
        'email',
        'address',
        'gst_number',
        'state',
        'state_code',
        'pin_code',
        'hsn_code',
        'gst_rate',
        'vehicle_number',
        'is_active'
    ];

    protected $casts = [
        'gst_rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Parent company relationship
    public function parent(): BelongsTo
    {
        return $this->belongsTo(B2BCompany::class, 'parent_id');
    }

    // Sub-companies relationship
    public function subCompanies(): HasMany
    {
        return $this->hasMany(B2BCompany::class, 'parent_id');
    }

    // Sales as main company
    public function mainCompanySales(): HasMany
    {
        return $this->hasMany(B2BSale::class, 'main_company_id');
    }

    // Sales as sub company
    public function subCompanySales(): HasMany
    {
        return $this->hasMany(B2BSale::class, 'sub_company_id');
    }

    // Scope for main companies only
    public function scopeMainCompanies($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scope for sub companies
    public function scopeSubCompanies($query)
    {
        return $query->whereNotNull('parent_id');
    }
}